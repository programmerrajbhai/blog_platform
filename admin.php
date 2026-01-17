<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Dhaka');
session_start();

/**
 * =========================================================
 * RajTech Admin Panel (Modern & Easy)
 * - Clean Writing Interface
 * - Real-time Split Preview
 * - Advanced Fields Hidden by Default
 * =========================================================
 */

$DATA_FILE  = __DIR__ . '/data/posts.json';
$DATA_DIR   = __DIR__ . '/data';
$UPLOAD_DIR = __DIR__ . '/uploads';
$UPLOAD_URL = 'uploads';

// ✅ Admin login
$ADMIN_USER = 'admin';
$ADMIN_PASS = '123456';

// --- Helper Functions ---
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function now_local(): string { return date('Y-m-d H:i:s'); }
function flash(string $msg): void { $_SESSION['flash'] = $msg; }
function get_flash(): string { $m = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return (string)$m; }
function ensure_dir(string $dir): void { if (!is_dir($dir)) @mkdir($dir, 0775, true); }

function read_posts(string $file): array {
  if (!file_exists($file)) return [];
  $j = file_get_contents($file);
  $d = json_decode($j ?: '[]', true);
  return is_array($d) ? $d : [];
}

function write_posts(string $file, array $data): bool {
  ensure_dir(dirname($file));
  $fp = @fopen($file, 'c+');
  if (!$fp) return false;
  if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }
  ftruncate($fp, 0);
  rewind($fp);
  $ok = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) !== false;
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
  return $ok;
}

function slugify(string $text): string {
  $text = trim(mb_strtolower($text));
  $text = preg_replace('~[^\pL\pN]+~u', '-', $text);
  $text = trim($text, '-');
  return $text !== '' ? $text : 'post';
}

function compute_read_minutes(string $html): int {
  $words = str_word_count(strip_tags($html));
  return max(1, (int)ceil($words / 200));
}

function random_token(int $bytes=12): string { return bin2hex(random_bytes($bytes)); }

function require_login(): void {
  if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: admin.php');
    exit();
  }
}

function find_index_by_id(array $posts, string $id): int {
  foreach ($posts as $i => $p) if (($p['id'] ?? '') === $id) return $i;
  return -1;
}

// Image handling
function ext_ok(string $ext): bool {
  return in_array(strtolower($ext), ['jpg','jpeg','png','webp'], true);
}

function make_thumb(string $src, string $dst, int $maxW=700): bool {
  if (!extension_loaded('gd')) return false;
  $info = @getimagesize($src);
  if (!$info) return false;
  [$w,$h] = $info;
  if ($w <= 0 || $h <= 0) return false;

  $ratio = $w / $h;
  $newW = min($maxW, $w);
  $newH = (int)round($newW / $ratio);

  $mime = $info['mime'] ?? '';
  $im = match($mime) {
      'image/jpeg' => @imagecreatefromjpeg($src),
      'image/png'  => @imagecreatefrompng($src),
      'image/webp' => @imagecreatefromwebp($src),
      default => false
  };

  if (!$im) return false;
  $thumb = imagecreatetruecolor($newW, $newH);
  if ($mime === 'image/png') {
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
  }
  imagecopyresampled($thumb, $im, 0,0,0,0, $newW,$newH, $w,$h);

  $ok = match($mime) {
      'image/jpeg' => imagejpeg($thumb, $dst, 85),
      'image/png'  => imagepng($thumb, $dst, 7),
      'image/webp' => imagewebp($thumb, $dst, 85),
      default => false
  };

  imagedestroy($im);
  imagedestroy($thumb);
  return $ok;
}

function post_preview_url(array $p): string {
  $slug = rawurlencode((string)($p['slug'] ?? ''));
  $tok  = rawurlencode((string)($p['preview_token'] ?? ''));
  return "single.php?slug={$slug}&token={$tok}";
}

// ------------------ Ensure Setup ------------------
ensure_dir($DATA_DIR);
ensure_dir($UPLOAD_DIR);
ensure_dir($UPLOAD_DIR . '/thumbs');
if (!file_exists($DATA_FILE)) @file_put_contents($DATA_FILE, "[]");

// ------------------ Logic ------------------
$action = (string)($_GET['action'] ?? '');
$logged = (bool)($_SESSION['admin_logged_in'] ?? false);
$flash  = get_flash();
$posts  = read_posts($DATA_FILE);

// Auth Actions
if ($action === 'logout') {
  session_destroy();
  header('Location: admin.php');
  exit();
}
if (isset($_POST['do_login'])) {
  $u = (string)($_POST['username'] ?? '');
  $p = (string)($_POST['password'] ?? '');
  if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
    $_SESSION['admin_logged_in'] = true;
    flash('Welcome back 👋');
    header('Location: admin.php');
    exit();
  }
  flash('Invalid credentials ❌');
  header('Location: admin.php');
  exit();
}

// Upload
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_login();
  header('Content-Type: application/json');
  if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    echo json_encode(['ok'=>false,'message'=>'No file']); exit();
  }
  $f = $_FILES['image'];
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!ext_ok($ext)) { echo json_encode(['ok'=>false,'message'=>'Only JPG/PNG/WEBP']); exit(); }
  
  $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $UPLOAD_DIR . '/' . $fileName;
  
  if (move_uploaded_file($f['tmp_name'], $dest)) {
    $thumbName = 'thumb-' . $fileName;
    make_thumb($dest, $UPLOAD_DIR . '/thumbs/' . $thumbName);
    echo json_encode(['ok'=>true, 'url'=>$UPLOAD_URL.'/'.$fileName, 'thumb'=>$UPLOAD_URL.'/thumbs/'.$thumbName]);
  } else {
    echo json_encode(['ok'=>false,'message'=>'Upload failed']);
  }
  exit();
}

// Save
if (isset($_POST['save_post'])) {
  require_login();
  $id = trim((string)($_POST['id'] ?? ''));
  $title = trim((string)($_POST['title'] ?? ''));
  $content = trim((string)($_POST['content'] ?? ''));
  
  if ($title === '') { flash('Title required!'); header('Location: admin.php?action=edit&id='.$id); exit(); }

  $slug = $_POST['slug'] ?: slugify($title);
  // unique slug check
  $baseSlug = $slug; $c=2;
  while(count(array_filter($posts, fn($p)=> ($p['id']!==$id && $p['slug']===$slug))) > 0) {
      $slug = $baseSlug . '-' . $c++;
  }

  $tagsRaw = (string)($_POST['tags'] ?? '');
  $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));

  $payload = [
    'id' => $id ?: bin2hex(random_bytes(8)),
    'title' => $title,
    'slug' => $slug,
    'category' => $_POST['category'] ?: 'Uncategorized',
    'author' => $_POST['author'] ?: 'RajTech',
    'status' => $_POST['status'] ?? 'draft',
    'published_at' => $_POST['published_at'] ?: now_local(),
    'read_minutes' => compute_read_minutes($content),
    'tags' => $tags,
    'image' => $_POST['image'] ?: '',
    'thumb' => $_POST['thumb'] ?: '',
    'meta_image' => $_POST['meta_image'] ?: '',
    'seo_title' => $_POST['seo_title'] ?: '',
    'seo_desc' => $_POST['seo_desc'] ?: '',
    'preview_token' => $_POST['preview_token'] ?: random_token(),
    'content' => $content,
    'updated_at' => now_local()
  ];

  $idx = find_index_by_id($posts, $payload['id']);
  if ($idx >= 0) $posts[$idx] = array_merge($posts[$idx], $payload);
  else array_unshift($posts, $payload); // Add to top

  write_posts($DATA_FILE, $posts);
  flash('Saved successfully ✅');

  $after = $_POST['after'] ?? 'dashboard';
  if ($after === 'continue') header('Location: admin.php?action=edit&id='.rawurlencode($payload['id']));
  elseif ($after === 'view') header('Location: single.php?slug='.rawurlencode($payload['slug']));
  else header('Location: admin.php');
  exit();
}

// Delete
if ($action === 'delete' && isset($_GET['id'])) {
  require_login();
  $posts = array_values(array_filter($posts, fn($p)=> $p['id'] !== $_GET['id']));
  write_posts($DATA_FILE, $posts);
  flash('Deleted 🗑️');
  header('Location: admin.php');
  exit();
}

// Edit Prep
$edit = null;
if ($action === 'edit') {
  require_login();
  $id = (string)($_GET['id'] ?? '');
  foreach ($posts as $p) if (($p['id']??'') === $id) { $edit = $p; break; }
  if (!$edit) $edit = [
    'id'=>'','title'=>'','slug'=>'','category'=>'Tech','author'=>'RajTech','status'=>'draft',
    'published_at'=>now_local(),'tags'=>[],'image'=>'','thumb'=>'','content'=>'<p>Start writing...</p>'
  ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin • RajTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = { theme:{ extend:{ fontFamily:{ sans:['Inter','sans-serif'] } } } }
  </script>
  <style>
    .editor { min-height: 400px; outline: none; line-height: 1.8; }
    .editor h2 { font-size: 1.5em; font-weight: 700; margin: 1em 0 0.5em; }
    .editor h3 { font-size: 1.25em; font-weight: 600; margin: 1em 0 0.5em; }
    .editor ul { list-style: disc; padding-left: 1.5em; margin: 1em 0; }
    .editor ol { list-style: decimal; padding-left: 1.5em; margin: 1em 0; }
    .editor blockquote { border-left: 4px solid #e5e7eb; padding-left: 1em; color: #4b5563; font-style: italic; }
    .editor pre { background: #1f2937; color: #fff; padding: 1em; border-radius: 0.5em; overflow-x: auto; margin: 1em 0; }
    .editor img { max-width: 100%; border-radius: 0.5em; margin: 1em 0; }
    .editor a { color: #2563eb; text-decoration: underline; }
    /* Zen Mode inputs */
    .zen-input { background: transparent; border: none; outline: none; width: 100%; }
    .zen-input:focus { ring: 0; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

<nav class="bg-white border-b h-14 flex items-center justify-between px-4 sticky top-0 z-40">
  <div class="flex items-center gap-4">
    <a href="admin.php" class="font-bold text-lg"><i class="fa-solid fa-cube text-blue-600"></i> RajTech Admin</a>
    <a href="index.php" target="_blank" class="text-sm text-gray-500 hover:text-blue-600"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
  </div>
  <?php if($logged): ?>
    <a href="admin.php?action=logout" class="text-xs font-semibold bg-gray-100 px-3 py-1.5 rounded-lg hover:bg-red-50 hover:text-red-600 transition">Logout</a>
  <?php endif; ?>
</nav>

<div class="max-w-7xl mx-auto p-4 md:p-6">

  <?php if($flash): ?>
    <div class="fixed bottom-5 right-5 bg-gray-900 text-white px-5 py-3 rounded-xl shadow-lg animate-bounce z-50 flex items-center gap-3">
      <i class="fa-solid fa-bell"></i> <?= e($flash) ?>
    </div>
  <?php endif; ?>

  <?php if(!$logged): ?>
    <div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-2xl shadow-sm border">
      <h1 class="text-2xl font-bold text-center mb-6">Login to Panel</h1>
      <form method="post" class="space-y-4">
        <input type="hidden" name="do_login" value="1">
        <input name="username" class="w-full px-4 py-3 rounded-xl bg-gray-50 border focus:border-blue-500 outline-none" placeholder="Username">
        <input name="password" type="password" class="w-full px-4 py-3 rounded-xl bg-gray-50 border focus:border-blue-500 outline-none" placeholder="Password">
        <button class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition">Login</button>
      </form>
    </div>

  <?php elseif($action === 'edit' && $edit): ?>
    <form method="post" onsubmit="return syncContent()">
      <input type="hidden" name="save_post" value="1">
      <input type="hidden" name="id" value="<?= e((string)$edit['id']) ?>">
      <input type="hidden" name="thumb" id="thumb" value="<?= e((string)($edit['thumb']??'')) ?>">
      <input type="hidden" name="preview_token" value="<?= e((string)($edit['preview_token']??'')) ?>">
      <input type="hidden" name="after" id="after" value="dashboard">
      <textarea name="content" id="content" class="hidden"></textarea>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <a href="admin.php" class="text-sm text-gray-500 hover:text-gray-900"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
          <div class="flex items-center gap-2 mt-1">
             <span class="text-xs font-bold uppercase tracking-wider text-gray-400"><?= $edit['id'] ? 'Editing' : 'New Post' ?></span>
             <span class="bg-<?= ($edit['status']??'draft')==='published'?'green':'orange' ?>-100 text-<?= ($edit['status']??'draft')==='published'?'green':'orange' ?>-700 text-[10px] px-2 py-0.5 rounded-full uppercase font-bold"><?= $edit['status']??'draft' ?></span>
          </div>
        </div>
        <div class="flex items-center gap-2">
           <button type="button" onclick="toggleSplitPreview()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
             <i class="fa-solid fa-columns"></i> Split Preview
           </button>
           <button type="submit" onclick="setAfter('dashboard')" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:border-blue-500 transition">Save</button>
           <button type="submit" onclick="setAfter('view')" class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 shadow-md transition">Publish / View</button>
        </div>
      </div>

      <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b bg-gray-50 px-4 py-2 flex flex-wrap gap-2 sticky top-0 z-10">
            <button type="button" onclick="cmd('bold')" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-solid fa-bold"></i></button>
            <button type="button" onclick="cmd('italic')" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-solid fa-italic"></i></button>
            <span class="w-px h-6 bg-gray-300 my-auto mx-1"></span>
            <button type="button" onclick="format('h2')" class="px-2 h-8 rounded hover:bg-white hover:shadow text-sm font-bold text-gray-600">H2</button>
            <button type="button" onclick="format('h3')" class="px-2 h-8 rounded hover:bg-white hover:shadow text-sm font-bold text-gray-600">H3</button>
            <span class="w-px h-6 bg-gray-300 my-auto mx-1"></span>
            <button type="button" onclick="cmd('insertUnorderedList')" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-solid fa-list-ul"></i></button>
            <button type="button" onclick="insertLink()" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-solid fa-link"></i></button>
            <button type="button" onclick="insertImage()" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-regular fa-image"></i></button>
            <button type="button" onclick="insertCode()" class="w-8 h-8 rounded hover:bg-white hover:shadow flex items-center justify-center text-gray-600"><i class="fa-solid fa-code"></i></button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 divide-x divide-gray-100" id="editorContainer">
            <div class="p-6 md:p-10">
              <input name="title" value="<?= e((string)($edit['title']??'')) ?>" 
                     class="zen-input text-3xl md:text-4xl font-extrabold text-gray-900 placeholder-gray-300 mb-6" 
                     placeholder="Enter your post title here..." autocomplete="off">
              
              <div id="editor" class="editor prose max-w-none text-lg text-gray-600" contenteditable="true" 
                   data-placeholder="Start writing your amazing story..."><?= $edit['content'] ?? '' ?></div>
            </div>

            <div id="livePreview" class="hidden bg-gray-50 p-6 md:p-10 overflow-y-auto h-[600px] border-l">
              <div class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest text-center">Live Preview</div>
              <h1 id="prevTitle" class="text-3xl font-extrabold text-gray-900 mb-4"><?= e($edit['title']??'Title') ?></h1>
              <img id="prevImg" src="<?= e($edit['image']??'') ?>" class="<?= empty($edit['image'])?'hidden':'' ?> w-full rounded-xl mb-6 shadow-sm object-cover max-h-60">
              <div id="prevContent" class="prose max-w-none prose-blue"></div>
            </div>
          </div>
        </div>

        <div class="space-y-5">
          <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-900 mb-3">Publishing</h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Status</label>
                <select name="status" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                  <option value="draft" <?= ($edit['status']??'')==='draft'?'selected':'' ?>>Draft</option>
                  <option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>Published</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Date</label>
                <input name="published_at" value="<?= e((string)($edit['published_at']??now_local())) ?>" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
              </div>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
             <h3 class="font-bold text-gray-900 mb-3">Featured Image</h3>
             <div class="relative group cursor-pointer border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 min-h-[150px] flex items-center justify-center overflow-hidden hover:border-blue-400 transition" onclick="document.getElementById('filePick').click()">
                <input type="file" id="filePick" class="hidden" accept="image/*">
                <img id="imgPreview" src="<?= e($edit['image']??'') ?>" class="absolute inset-0 w-full h-full object-cover <?= empty($edit['image'])?'hidden':'' ?>">
                <div class="text-center p-4 text-gray-400 group-hover:text-blue-500">
                  <i class="fa-solid fa-cloud-arrow-up text-2xl mb-2"></i>
                  <div class="text-xs font-bold">Click to Upload</div>
                </div>
             </div>
             <input type="hidden" name="image" id="imageUrl" value="<?= e($edit['image']??'') ?>">
             <div class="mt-3 flex gap-2">
               <input type="text" placeholder="Or paste URL..." onchange="updateImage(this.value)" class="flex-1 text-xs border rounded px-2 py-1 bg-gray-50">
               <button type="button" onclick="document.getElementById('imageUrl').value=''; updateImage('')" class="text-xs text-red-500 hover:underline">Remove</button>
             </div>
          </div>

          <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-900 mb-3">Organization</h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Category</label>
                <input name="category" list="catList" value="<?= e($edit['category']??'Tech') ?>" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <datalist id="catList"><option value="Tech"><option value="News"><option value="Tutorial"></datalist>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Tags</label>
                <input name="tags" value="<?= e(implode(', ', (array)($edit['tags']??[]))) ?>" placeholder="php, coding..." class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
              </div>
            </div>
          </div>

          <details class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 group">
            <summary class="font-bold text-gray-900 cursor-pointer list-none flex justify-between items-center">
              <span>Advanced / SEO</span>
              <i class="fa-solid fa-chevron-down group-open:rotate-180 transition"></i>
            </summary>
            <div class="mt-4 space-y-3 pt-3 border-t">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Slug (URL)</label>
                <input name="slug" value="<?= e($edit['slug']??'') ?>" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Author</label>
                <input name="author" value="<?= e($edit['author']??'RajTech') ?>" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">SEO Description</label>
                <textarea name="seo_desc" rows="3" class="w-full mt-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs"><?= e($edit['seo_desc']??'') ?></textarea>
              </div>
            </div>
          </details>

        </div>
      </div>
    </form>

  <?php else: ?>
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500">Manage your content easily.</p>
      </div>
      <a href="admin.php?action=edit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> New Post
      </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
       <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
         <div class="text-gray-400 text-xs font-bold uppercase">Total Posts</div>
         <div class="text-3xl font-bold text-gray-900 mt-1"><?= count($posts) ?></div>
       </div>
       <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
         <div class="text-green-500 text-xs font-bold uppercase">Published</div>
         <div class="text-3xl font-bold text-gray-900 mt-1"><?= count(array_filter($posts,fn($p)=>($p['status']??'')==='published')) ?></div>
       </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="p-4 font-bold text-gray-500">Post Title</th>
            <th class="p-4 font-bold text-gray-500 w-32">Status</th>
            <th class="p-4 font-bold text-gray-500 w-40">Date</th>
            <th class="p-4 font-bold text-gray-500 text-right w-40">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach($posts as $p): ?>
            <tr class="hover:bg-gray-50 group">
              <td class="p-4">
                <div class="font-bold text-gray-900"><?= e($p['title']??'No Title') ?></div>
                <div class="text-xs text-gray-400"><?= e($p['category']??'') ?></div>
              </td>
              <td class="p-4">
                <?php $st = $p['status']??'draft'; ?>
                <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $st==='published'?'bg-green-100 text-green-700':'bg-orange-100 text-orange-700' ?>">
                  <?= $st ?>
                </span>
              </td>
              <td class="p-4 text-gray-500 text-xs"><?= date('M d, Y', strtotime($p['published_at']??'now')) ?></td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                  <a href="admin.php?action=edit&id=<?= $p['id'] ?>" class="p-2 text-gray-500 hover:text-blue-600 bg-white border rounded-lg shadow-sm"><i class="fa-solid fa-pen"></i></a>
                  <a href="admin.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Delete?')" class="p-2 text-gray-500 hover:text-red-600 bg-white border rounded-lg shadow-sm"><i class="fa-solid fa-trash"></i></a>
                  <a href="<?= post_preview_url($p) ?>" target="_blank" class="p-2 text-gray-500 hover:text-gray-900 bg-white border rounded-lg shadow-sm"><i class="fa-solid fa-eye"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($posts)): ?>
            <tr><td colspan="4" class="p-8 text-center text-gray-400">No posts found. Create one!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<script>
  // --- Core Editor Functions ---
  function cmd(c){ document.execCommand(c,false,null); updatePreview(); }
  function format(tag){ document.execCommand('formatBlock', false, tag); updatePreview(); }
  function insertLink(){ 
    let u=prompt('URL:'); if(u) document.execCommand('createLink',false,u); 
    updatePreview(); 
  }
  function insertImage(){ 
    let u=prompt('Image URL:'); if(u) document.execCommand('insertImage',false,u); 
    updatePreview(); 
  }
  function insertCode(){
    let c=prompt('Paste Code:'); if(c) {
      let h=`<pre><code>${c.replace(/</g,'&lt;')}</code></pre>`;
      document.execCommand('insertHTML',false,h);
    }
    updatePreview();
  }

  // --- Realtime Preview ---
  const ed = document.getElementById('editor');
  const tit = document.querySelector('input[name="title"]');
  const prevContent = document.getElementById('prevContent');
  const prevTitle = document.getElementById('prevTitle');
  
  function updatePreview() {
    if(!ed) return;
    if(prevContent) prevContent.innerHTML = ed.innerHTML;
    if(prevTitle && tit) prevTitle.innerText = tit.value || 'Title';
  }

  // Bind events
  if(ed) ed.addEventListener('input', updatePreview);
  if(tit) tit.addEventListener('input', updatePreview);

  // Split View Toggle
  function toggleSplitPreview() {
    const prev = document.getElementById('livePreview');
    const container = document.getElementById('editorContainer');
    
    if(prev.classList.contains('hidden')) {
      prev.classList.remove('hidden');
      container.classList.remove('md:grid-cols-2'); // reset to force grid apply
      void container.offsetWidth; // trigger reflow
      container.classList.add('md:grid-cols-2');
      updatePreview();
    } else {
      prev.classList.add('hidden');
      container.classList.remove('md:grid-cols-2');
    }
  }

  // --- Image Upload ---
  const filePick = document.getElementById('filePick');
  if(filePick) {
    filePick.addEventListener('change', async function() {
      if(!this.files[0]) return;
      const fd = new FormData(); fd.append('image', this.files[0]);
      
      // visual loading state
      const wrap = this.parentElement;
      wrap.style.opacity = '0.5';
      
      try {
        let res = await fetch('admin.php?action=upload', {method:'POST', body:fd});
        let data = await res.json();
        if(data.ok) {
          updateImage(data.url);
          if(document.getElementById('thumb')) document.getElementById('thumb').value = data.thumb;
        } else {
          alert(data.message);
        }
      } catch(e) { alert('Upload failed'); }
      wrap.style.opacity = '1';
    });
  }

  function updateImage(url) {
    document.getElementById('imageUrl').value = url;
    const img = document.getElementById('imgPreview');
    const prevImg = document.getElementById('prevImg');
    
    if(url) {
      img.src = url; img.classList.remove('hidden');
      if(prevImg) { prevImg.src = url; prevImg.classList.remove('hidden'); }
    } else {
      img.classList.add('hidden');
      if(prevImg) prevImg.classList.add('hidden');
    }
  }

  // --- Save Logic ---
  function setAfter(val) { document.getElementById('after').value = val; }
  function syncContent() {
    if(ed) document.getElementById('content').value = ed.innerHTML;
    return true;
  }
</script>
</body>
</html>