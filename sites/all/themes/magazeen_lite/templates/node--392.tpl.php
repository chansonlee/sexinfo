<?php include 'utils/content_tag.php' ?>

<?php
  // Decode /data/topics.json as associative array
  function getTopics() {
    $fileData = file_get_contents("data/topics.json", true);
    return json_decode($fileData, true);
  }

  // TODO: remove this
  $GLOBALS['topics'] = getTopics();

  // Render a half-width topic box for a specified topic name
  //
  //  $size - The size of the section, 'half' or 'quarter'
  //  $topicName - Name of the topic JSON section, e.g. 'The Sexual Response Cycle'
  //
  // The heredoc can't be indented!
  // See http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
  function renderTopicSection($size, $topicName) {
    $topics = $GLOBALS['topics'];
    $topic  = $topics[$topicName];
    $image  = path_to_theme() . '/images/topics/' .  $topic['image'];

    // Pre-construct children list items to substitute into heredoc string
    // Ex: <li class="text-on-image-article"><a href="http://...">Sex Around The World</a></li>
    $children = "";
    foreach($topic['children'] as $name => $url) {
      $link = content_tag("a", $name, array("href" => $url));
      $children .= content_tag('li', $link, array('class' => 'text-on-image-article'));
    }

return <<<HTML
<div class="topic-{$size}">
  <div class="text-on-image" style="background-image: url('$image')">
    <div class="text-on-image-tint">
    <div class="text-on-image-text">{$topicName}</div>
      <ul class="text-on-image-articles">{$children}</ul>
    </div>
  </div>
</div>
HTML;
  }

  function renderTopicHalf($topicName) {
    return renderTopicSection('half', $topicName);
  }

  function renderTopicQuarter($topicName) {
    return renderTopicSection('quarter', $topicName);
  }

  function generateTopics() {
    $topicsresult = db_query('SELECT * FROM taxonomy_term_data A, taxonomy_term_hierarchy B WHERE A.tid=B.tid AND A.vid=3 AND B.parent=0 ORDER BY weight ASC');

    $topics = [];

    foreach ($topicsresult as $topicresult) {
      $topic = [];
      $topic['name'] = $topicresult->name;
      $topic['tid'] = $topicresult->tid;
      $topic['sections'] = generateSections($topic);
      $topics[] = $topic;
    }

    return $topics;
  }

  function generateSections($topic) {
    $sectionsresult = db_query('SELECT * FROM taxonomy_term_hierarchy A, taxonomy_term_data B WHERE A.tid = B.tid AND parent=' . $topic['tid']);

    $sections = [];
    foreach ($sectionsresult as $sectionresult) {
      $section = [];
      $section['name'] = $sectionresult->name;
      $section['image'] = 'sites/all/themes/magazeen_lite/images/topics/kinky_sex_paraphilia.jpg';
      $section['tid'] = $sectionresult->tid;
      $section['articles'] = generateArticles($section);
      $section['rendersize'] = sizeof($section['articles']) > 6 ? 2 : 1;
      $sections[] = $section;
    }

    return $sections;
  }

  function generateArticles($section) {
    // Generate children html which is links to articles
    // The status=1 part makes sure we only select published articles
    // This is because there are a lot of cases were we have old, 
    // and terrible articles in circulation that we don't want normal users to see.
    $articlesresult = db_query('SELECT DISTINCT * FROM taxonomy_index A, node B WHERE A.nid = B.nid AND B.status=1 AND A.tid=' . $section['tid']);
    $articles = [];
    foreach($articlesresult as $articleresult) {
      $article = [];
      $article['nid'] = $articleresult->nid;
      $article['name'] = $articleresult->title;
      $articles[] = $article;
    }

    return $articles;
  }

  function optimizeSectionLayout($sections) {
    $leftsections = [];
    $rightsections = [];

    $leftsize = 0;
    $rightsize = 0;

    // Count the total size of the sections by quarters
    $totalsize = 0;
    $lastlargeindex = -1;
    $lastsmallindex = -1;
    for ($i = 0; $i < sizeof($sections); $i++) {
      $section = $sections[$i];
      $totalsize += $section['rendersize'];
      if ($section['rendersize'] == 1) $lastsmallindex = $i;
      else $lastlargeindex = $i;
    }

    // If the last element isn't small, make it small
    if ($lastlargeindex != -1 && $lastsmallindex != -1 && $lastlargeindex > $lastsmallindex) {
      $temp = $sections[$lastsmallindex];
      $sections[$lastsmallindex] = $sections[$lastlargeindex];
      $sections[$lastlargeindex] = $temp;
      $swap = $lastlargeindex;
      $lastlargeindex = $lastsmallindex;
      $lastsmallindex = $swap;
    }

    // Modify based on the way sections work
    $howmanytomakebigger = $totalsize % 2;
    foreach ($sections as $section) {
      if ($section['rendersize'] == 1 && $howmanytomakebigger > 0) {
        $howmanytomakebigger -= 1;
        $section['rendersize'] = 2;
      }

      $size = $section['rendersize'];
      if ($leftsize <= $rightsize) {
        $leftsections[] = $section;
        $leftsize += $size;
      } else {
        $rightsections[] = $section;
        $rightsize += $size;
      }
    }

    return array($leftsections, $rightsections);
  }

  function renderNav($topics) {
    // Generate html from db result
    $result = <<<HTML
    <ul class="topics-nav">
      <div class="header-container">
        <h4>Explore Our Topics!</h4>
      </div>
HTML;

    // For each topic, make a link in the list
    foreach($topics as $topic) {
      $name = $topic['name'];
      $result = $result . sprintf("<li><a href='#%s'>%s</a></li>", urlencode($name), $name);
    }

    // Return generated HTML
    return $result . "</ul>";
  }

  function renderTopics($topics) {
    $result = "<div class=\"topics-container\">";

    // For each topic, make a link in the list
    foreach($topics as $topic) {
      $result = $result . renderTopic($topic);
    }

    // Return generated HTML
    return $result . "</div>";
  }


  function renderTopic($topic) {
    $name = $topic['name'];
    $target = urlencode($name);
    $sections = renderSections($topic['sections']);
    return <<<HTML
<div class="parent-topic" id="$target" class="js-masonry" data-masonry-options='{ "columnWidth": 200, "itemSelector": ".topic-quarter" }'>
  <h2>$name</h2>
  $sections
</div><!-- .parent-topic -->
HTML;
  }

  function renderSections($sections) {
    $left_html = '<div class="grid-left">';
    $right_html = '<div class="grid-right">';

    list($leftsections, $rightsections) = optimizeSectionLayout($sections);
    foreach ($leftsections as $section) {
      $left_html .= renderSection($section);
    }
    foreach ($rightsections as $section) {
      $right_html .= renderSection($section);
    }

    return $left_html . "</div>" . $right_html . "</div>" ;
  }

  function renderSection($section) {
    $children_html = '';
    $articles = $section['articles'];

    foreach($articles as $article) {
      $children_html = $children_html . sprintf("<li class='text-on-image-article'><a href=\"node/%d\">%s</a></li>", $article['nid'], $article['name']);
    }

    $section_name = $section['name'];
    $size = $section['rendersize'] == 1 ? 'quarter' : 'half';
    $image = 'sites/all/themes/magazeen_lite/images/topics/kinky_sex_paraphilia.jpg';
    $section_html = <<<HTML
<div class="topic-{$size}">
  <div class="text-on-image" style="background-image: url('$image')">
    <div class="text-on-image-tint">
    <div class="text-on-image-text">{$section_name}</div>
      <ul class="text-on-image-articles">{$children_html}</ul>
    </div>
  </div>
</div>
HTML;
    return $section_html;
  }

  $topics = generateTopics();
  echo renderNav($topics);
  echo renderTopics($topics);
  ?>
</div><!-- .topics-container -->
