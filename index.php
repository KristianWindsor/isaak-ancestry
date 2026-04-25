<?php
function loadStory($ymlPath) {
    if (!file_exists($ymlPath)) {
        die('story.yml not found at ' . $ymlPath);
    }
    $result = [];
    foreach (file($ymlPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^(\w+):\s+(.+)$/', $line, $m)) {
            $result[$m[1]] = trim($m[2]);
        }
    }
    return $result;
}

function parseChapterYaml($path) {
    $result = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^(\w+):\s+(.+)$/', $line, $m)) {
            $result[$m[1]] = trim($m[2]);
        }
    }
    return $result;
}

function loadChapters($storyDir) {
    $dirs = glob($storyDir . '/ch*', GLOB_ONLYDIR);
    natsort($dirs);
    $chapters = [];
    $num = 1;
    foreach ($dirs as $dir) {
        $ymlPath = $dir . '/chapter.yml';
        if (!file_exists($ymlPath)) { $num++; continue; }
        $yml = parseChapterYaml($ymlPath);
        $chapters[$num] = [
            'title'       => $yml['title'] ?? basename($dir),
            'description' => implode(' — ', array_filter([$yml['place'] ?? null, $yml['date'] ?? null])),
            'dir'         => $dir,
        ];
        $num++;
    }
    return $chapters;
}

$pageYml  = loadStory(__DIR__ . '/story/story.yml');
$siteInfo = ['title' => $pageYml['title']];
$stories  = [
    'isaak' => [
        'title'       => $pageYml['title'],
        'description' => $pageYml['description'],
        'dir'         => __DIR__ . '/story',
        'chapters'    => loadChapters(__DIR__ . '/story'),
    ],
];


function getMediaFiles($chapterDir) {
    $mediaFiles = [];
    if (!is_dir($chapterDir)) return $mediaFiles;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($chapterDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'])) {
            $absPath = $file->getPathname();
            $mediaFiles[] = [
                'path' => ltrim(str_replace(__DIR__, '', $absPath), '/'),
                'type' => in_array($ext, ['mp4', 'webm', 'mov']) ? 'video' : 'image',
            ];
        }
    }
    usort($mediaFiles, fn($a, $b) => strnatcmp($a['path'], $b['path']));
    return $mediaFiles;
}

// Get current page parameters
$story = $_GET['story'] ?? 'isaak';
$chapter = $_GET['chapter'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($chapter) {
            echo htmlspecialchars($stories[$story]['title'] . ' — Chapter ' . $chapter);
        } else {
            echo htmlspecialchars($siteInfo['title']);
        }
        ?>
    </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <a href="index.php" class="site-title"><?php echo htmlspecialchars($siteInfo['title']); ?></a>
    </header>

    <main>
        <?php if ($story && !$chapter): ?>
            <!-- Story Page - List of Chapters -->
            <?php if (isset($stories[$story])): ?>
                <h1 class="story-title"><?php echo htmlspecialchars($stories[$story]['title']); ?></h1>
                <p class="story-description"><?php echo htmlspecialchars($stories[$story]['description']); ?></p>
                
                <div class="chapters-grid">
                    <?php foreach ($stories[$story]['chapters'] as $chapterNum => $chapterData): ?>
                        <a href="index.php?story=<?php echo urlencode($story); ?>&chapter=<?php echo $chapterNum; ?>" class="chapter-card">
                            <h3>
                                Chapter <?php echo $chapterNum; ?>: <?php echo htmlspecialchars($chapterData['title']); ?>
                            </h3>
                            <p><?php echo htmlspecialchars($chapterData['description']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Story not found. <a href="index.php">Return to homepage</a></p>
            <?php endif; ?>

        <?php elseif ($story && $chapter): ?>
            <!-- Chapter Page - Display Media -->
            <?php if (isset($stories[$story]) && isset($stories[$story]['chapters'][$chapter])): ?>
                <h1 class="story-title"><?php echo htmlspecialchars($stories[$story]['title']); ?></h1>
                <h2 class="chapter-title">Chapter <?php echo $chapter; ?>: <?php echo htmlspecialchars($stories[$story]['chapters'][$chapter]['title']); ?></h2>
                <p class="chapter-description"><?php echo htmlspecialchars($stories[$story]['chapters'][$chapter]['description']); ?></p>
                
                <div class="chapter-navigation">
                    <?php if ($chapter > 1): ?>
                        <a href="index.php?story=<?php echo urlencode($story); ?>&chapter=<?php echo ($chapter - 1); ?>" class="nav-btn prev">← Previous Chapter</a>
                    <?php endif; ?>
                    
                    <?php if ($chapter < count($stories[$story]['chapters'])): ?>
                        <a href="index.php?story=<?php echo urlencode($story); ?>&chapter=<?php echo ($chapter + 1); ?>" class="nav-btn next">Next Chapter →</a>
                    <?php endif; ?>
                </div>
                
                <div class="chapter-contents">
                    <?php 
                    $chapterDir = $stories[$story]['chapters'][$chapter]['dir'];
                    $mediaFiles = getMediaFiles($chapterDir);
                    if (empty($mediaFiles)): 
                    ?>
                        <p>No media files found for this chapter.</p>
                    <?php else: ?>
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php if ($media['type'] === 'video'): ?>
                                <video src="<?php echo htmlspecialchars($media['path']); ?>" class="page-video" autoplay muted loop playsinline></video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($media['path']); ?>" loading="lazy">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chapter-navigation">
                    <?php if ($chapter > 1): ?>
                        <a href="index.php?story=<?php echo urlencode($story); ?>&chapter=<?php echo ($chapter - 1); ?>" class="nav-btn prev">← Previous Chapter</a>
                    <?php endif; ?>
                    
                    <?php if ($chapter < count($stories[$story]['chapters'])): ?>
                        <a href="index.php?story=<?php echo urlencode($story); ?>&chapter=<?php echo ($chapter + 1); ?>" class="nav-btn next">Next Chapter →</a>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <p>Chapter not found. <a href="index.php?story=<?php echo urlencode($story); ?>">Return to story</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
    </footer>
</body>
</html>