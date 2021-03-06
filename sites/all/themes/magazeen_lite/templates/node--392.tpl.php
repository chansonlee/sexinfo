<?php include 'utils/content_tag.php' ?>
<?php include_once 'utils/topics.php' ?>

<?php
  
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
      $result = $result . sprintf("<li><a href='#%s'>%s</a></li>", strip($name),  htmlspecialchars($name));
    }

    // Return generated HTML
    return $result . "</ul>";
  }

  function renderTopics($topics, $images) {
    $result = "<div class='topics-container'>";

    // For each topic, make a link in the list
    foreach($topics as $topic) {
      $result = $result . renderTopic($topic, $images);
    }

    // Return generated HTML
    return $result . "</div>";
  }

  function renderTopic($topic, $images) {
    $name = $topic['name'];
    $target = strip($name);
    $sections = renderSections($topic['sections'], $images);
    return <<<HTML
<div class="parent-topic" id="$target">
  <h2>$name</h2>
  $sections
</div><!-- .parent-topic -->
HTML;
  }

  function renderSections($sections, $images) {
    $left_html = '<div class="grid-left">';
    $right_html = '<div class="grid-right">';

    list($leftsections, $rightsections) = optimizeSectionLayout($sections);
    foreach ($leftsections as $section) {
      $left_html .= renderSection($section, $images);
    }
    foreach ($rightsections as $section) {
      $right_html .= renderSection($section, $images);
    }

    return $left_html . "</div>" . $right_html . "</div>" ;
  }

  function renderSection($section, $images) {
    $children_html = '';
    $articles = $section['articles'];

    foreach($articles as $article) {
      $children_html .= sprintf("<li class='text-on-image-article'><a href='node/%d'>%s</a></li>", $article['nid'], $article['name']);
    }

    $section_name = $section['name'];
    $size = $section['rendersize'] == 1 ? 'quarter' : 'half';
    $image = path_to_theme() . '/images/topics/' . $images[sprintf("%d", $section['tid'])]['image'];
    $section_html = <<<HTML
<div class="topic-{$size}">
  <div class="text-on-image" style="background-image: url('/sexinfo/$image')">
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
  $images = getImagesForTopics();
  echo renderNav($topics);
  echo renderTopics($topics, $images);
  ?>
</div><!-- .topics-container -->
