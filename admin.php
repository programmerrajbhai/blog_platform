<?php
session_start();
require 'db.php';

// ---------------------------------------------------------
// 1. AUTHENTICATION
// ---------------------------------------------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

if (isset($_POST['login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === '123456') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $error = "❌ Invalid Credentials";
    }
}

// ---------------------------------------------------------
// 2. FETCH DATA
// ---------------------------------------------------------
$cats = [];
if (isset($_SESSION['admin_logged_in'])) {
    $stmt_cat = $conn->query("SELECT DISTINCT category FROM posts ORDER BY category ASC");
    $cats = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
}

// ---------------------------------------------------------
// 3. PUBLISH LOGIC (With Image Upload)
// ---------------------------------------------------------
$msg = ""; $msg_type = "";

if (isset($_POST['publish']) && isset($_SESSION['admin_logged_in'])) {
    $title = trim($_POST['title']);
    $category = !empty($_POST['new_category']) ? $_POST['new_category'] : $_POST['category_select'];
    $excerpt = $_POST['excerpt'];
    $content = $_POST['content'];
    $schedule_date = $_POST['scheduled_at'];
    
    // --- IMAGE HANDLING ---
    $image_final = "";
    
    // Case A: File Upload
    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Simple Validation
        if(in_array($imageFileType, ['jpg','png','jpeg','webp'])) {
            if(move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
                $image_final = $target_file; // Path saved to DB
            } else {
                $msg = "❌ Image upload failed!"; $msg_type = "error";
            }
        } else {
            $msg = "❌ Only JPG, PNG, WEBP allowed!"; $msg_type = "error";
        }
    } 
    // Case B: URL Input
    else if (!empty($_POST['image_url'])) {
        $image_final = $_POST['image_url'];
    }

    // --- SAVE TO DB ---
    if (!empty($title) && !empty($image_final) && empty($msg)) {
        $current_date = date('Y-m-d H:i:s');
        if ($schedule_date > $current_date) {
            $status = 'scheduled';
            $final_date = $schedule_date;
            $alert_msg = "🕒 Scheduled for " . date("M d, h:i A", strtotime($final_date));
        } else {
            $status = 'published';
            $final_date = $current_date;
            $alert_msg = "✅ Published Successfully!";
        }

        $sql = "INSERT INTO posts (title, category, image_url, excerpt, content, status, scheduled_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if($stmt->execute([$title, $category, $image_final, $excerpt, $content, $status, $final_date])) {
            $msg = $alert_msg; $msg_type = "success";
        } else {
            $msg = "Database Error"; $msg_type = "error";
        }
    } else if(empty($image_final)) {
        $msg = "⚠️ Image is required!"; $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Studio - RajTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: { brand: { blue: '#2563EB', dark: '#0F172A', bg: '#F8FAFC' } },
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar for Editor */
        textarea::-webkit-scrollbar { width: 8px; }
        textarea::-webkit-scrollbar-track { background: #f1f1f1; }
        textarea::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        textarea::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Preview Styles */
        .prose h2 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; color: #1e293b; }
        .prose p { margin-bottom: 1em; line-height: 1.7; color: #475569; }
        .prose pre { background: #1e293b; color: #e2e8f0; padding: 1em; border-radius: 0.5em; overflow-x: auto; font-family: 'JetBrains Mono'; font-size: 0.9em; }
        .prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1em; }
    </style>
</head>
<body class="bg-brand-bg font-sans antialiased h-screen overflow-hidden">

    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-brand-dark to-slate-900">
        <div class="bg-white/10 backdrop-blur-xl border border-white/10 p-10 rounded-3xl shadow-2xl w-full max-w-sm animate-fade-in">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-brand-blue rounded-2xl flex items-center justify-center mx-auto mb-4 text-white text-2xl shadow-lg shadow-blue-500/40">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Creator Studio</h2>
            </div>
            <?php if(isset($error)): ?>
                <div class="bg-red-500/20 text-red-200 text-xs p-3 rounded-lg mb-4 text-center border border-red-500/30"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="text" name="username" placeholder="Username" class="w-full bg-slate-800/50 border border-slate-600 text-white px-4 py-3 rounded-xl focus:outline-none focus:border-brand-blue transition" required>
                <input type="password" name="password" placeholder="Password" class="w-full bg-slate-800/50 border border-slate-600 text-white px-4 py-3 rounded-xl focus:outline-none focus:border-brand-blue transition" required>
                <button type="submit" name="login" class="w-full bg-brand-blue hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30">Enter Dashboard</button>
            </form>
        </div>
    </div>

    <?php else: ?>
    
    <div class="flex h-full">
        
        <aside class="w-20 md:w-64 bg-white border-r border-gray-200 flex flex-col z-20 transition-all duration-300">
            <div class="h-20 flex items-center justify-center md:justify-start md:px-8 border-b border-gray-100">
                <span class="text-2xl font-bold text-brand-dark hidden md:block">Raj<span class="text-brand-blue">Tech</span></span>
                <span class="text-2xl font-bold text-brand-blue md:hidden"><i class="fa-solid fa-bolt"></i></span>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="#" class="flex items-center gap-3 px-4 py-3.5 bg-blue-50 text-brand-blue rounded-xl font-medium shadow-sm">
                    <i class="fa-solid fa-pen-nib text-lg"></i> <span class="hidden md:block">New Article</span>
                </a>
                <a href="index.php" target="_blank" class="flex items-center gap-3 px-4 py-3.5 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-xl font-medium transition">
                    <i class="fa-solid fa-arrow-up-right-from-square text-lg"></i> <span class="hidden md:block">View Site</span>
                </a>
            </nav>
            <div class="p-4 border-t border-gray-100">
                <a href="?logout=true" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl font-medium transition justify-center md:justify-start">
                    <i class="fa-solid fa-power-off text-lg"></i> <span class="hidden md:block">Logout</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto relative">
            
            <form method="POST" enctype="multipart/form-data" class="max-w-7xl mx-auto p-4 md:p-8 pb-32">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Write New Story</h1>
                        <p class="text-gray-500 text-sm mt-1">Create impactful content for your audience.</p>
                    </div>
                    <?php if($msg): ?>
                        <div class="px-5 py-2.5 rounded-full text-sm font-bold flex items-center gap-2 shadow-sm animate-pulse
                            <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-check' : 'fa-triangle-exclamation'; ?>"></i> <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="fixed bottom-4 right-4 md:hidden z-50">
                        <button type="submit" name="publish" class="bg-brand-blue text-white w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-xl">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group focus-within:ring-2 ring-brand-blue/20 transition">
                            <input type="text" name="title" placeholder="Enter Article Title Here..." class="w-full text-3xl font-bold text-gray-800 placeholder-gray-300 border-none focus:ring-0 p-0 bg-transparent" required>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[650px]">
                            <div class="flex justify-between items-center px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                                <div class="flex gap-1 text-gray-500">
                                    <button type="button" onclick="insertTag('<b>', '</b>')" class="p-2 hover:bg-white hover:text-brand-blue rounded transition" title="Bold"><i class="fa-solid fa-bold"></i></button>
                                    <button type="button" onclick="insertTag('<i>', '</i>')" class="p-2 hover:bg-white hover:text-brand-blue rounded transition" title="Italic"><i class="fa-solid fa-italic"></i></button>
                                    <button type="button" onclick="insertTag('<h2>', '</h2>')" class="p-2 hover:bg-white hover:text-brand-blue rounded transition" title="Heading"><i class="fa-solid fa-heading"></i></button>
                                    <div class="w-px h-6 bg-gray-300 mx-2 self-center"></div>
                                    <button type="button" onclick="insertTag('<pre>', '</pre>')" class="p-2 hover:bg-white hover:text-brand-blue rounded transition" title="Code Block"><i class="fa-solid fa-code"></i></button>
                                </div>
                                <div class="flex bg-gray-200 rounded-lg p-1">
                                    <button type="button" onclick="switchTab('write')" id="btn-write" class="px-4 py-1 text-xs font-bold rounded-md bg-white text-gray-800 shadow-sm transition">Write</button>
                                    <button type="button" onclick="switchTab('preview')" id="btn-preview" class="px-4 py-1 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition">Preview</button>
                                </div>
                            </div>

                            <textarea id="editor-area" name="content" class="w-full flex-1 p-6 resize-none border-none focus:ring-0 text-gray-700 font-mono text-base leading-relaxed" placeholder="Start writing your amazing story..." required></textarea>
                            
                            <div id="preview-area" class="w-full flex-1 p-8 overflow-y-auto hidden prose max-w-none">
                                <p class="text-gray-400 italic text-center mt-20">Preview will appear here...</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-rocket text-brand-blue"></i> Publish</h3>
                            <div class="mb-4">
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-2">Schedule (Optional)</label>
                                <input type="datetime-local" name="scheduled_at" class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-blue focus:ring-brand-blue/20">
                            </div>
                            <button type="submit" name="publish" class="w-full bg-brand-blue hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30">
                                Publish Now
                            </button>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-4">Category</h3>
                            <select name="category_select" id="catSelect" class="w-full border-gray-200 rounded-xl text-sm mb-3 focus:border-brand-blue focus:ring-brand-blue/20" onchange="document.getElementById('newCatInput').value = ''">
                                <option value="">Select Existing</option>
                                <?php foreach($cats as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="new_category" id="newCatInput" placeholder="...or create new" class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-blue focus:ring-brand-blue/20" oninput="document.getElementById('catSelect').value = ''">
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-4">Featured Image</h3>
                            
                            <div class="flex border-b border-gray-100 mb-4">
                                <button type="button" onclick="toggleImageInput('upload')" id="tab-upload" class="flex-1 pb-2 text-sm font-bold text-brand-blue border-b-2 border-brand-blue">Upload</button>
                                <button type="button" onclick="toggleImageInput('url')" id="tab-url" class="flex-1 pb-2 text-sm font-bold text-gray-400">Link</button>
                            </div>

                            <div id="input-upload">
                                <label class="w-full h-32 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:border-brand-blue hover:bg-blue-50 transition group text-gray-400">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl mb-2 group-hover:text-brand-blue transition"></i>
                                    <span class="text-xs group-hover:text-brand-blue">Click to Upload</span>
                                    <input type="file" name="image_file" class="hidden" accept="image/*" onchange="previewFile(this)">
                                </label>
                            </div>

                            <div id="input-url" class="hidden">
                                <input type="text" name="image_url" id="urlField" placeholder="https://..." class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-blue focus:ring-brand-blue/20" oninput="document.getElementById('preview-img').src = this.value; document.getElementById('preview-box').classList.remove('hidden');">
                            </div>

                            <div id="preview-box" class="mt-4 hidden relative rounded-xl overflow-hidden border border-gray-200">
                                <img id="preview-img" src="" class="w-full h-40 object-cover">
                                <button type="button" onclick="clearImage()" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs flex items-center justify-center hover:bg-red-600 shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-2">Excerpt</h3>
                            <textarea name="excerpt" rows="3" class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-blue focus:ring-brand-blue/20" placeholder="Short description..." required></textarea>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // 1. Editor Tabs & Preview
        function switchTab(mode) {
            const editor = document.getElementById('editor-area');
            const preview = document.getElementById('preview-area');
            const btnWrite = document.getElementById('btn-write');
            const btnPrev = document.getElementById('btn-preview');

            if(mode === 'preview') {
                editor.classList.add('hidden');
                preview.classList.remove('hidden');
                preview.innerHTML = editor.value || '<p class="text-gray-400 italic text-center mt-20">Nothing to preview...</p>';
                
                btnPrev.className = "px-4 py-1 text-xs font-bold rounded-md bg-white text-gray-800 shadow-sm transition";
                btnWrite.className = "px-4 py-1 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition";
            } else {
                editor.classList.remove('hidden');
                preview.classList.add('hidden');
                
                btnWrite.className = "px-4 py-1 text-xs font-bold rounded-md bg-white text-gray-800 shadow-sm transition";
                btnPrev.className = "px-4 py-1 text-xs font-bold rounded-md text-gray-500 hover:text-gray-700 transition";
            }
        }

        // 2. Toolbar Logic
        function insertTag(start, end) {
            const textarea = document.getElementById('editor-area');
            const selectionStart = textarea.selectionStart;
            const selectionEnd = textarea.selectionEnd;
            const oldText = textarea.value;
            const selectedText = oldText.substring(selectionStart, selectionEnd);
            
            textarea.value = oldText.substring(0, selectionStart) + start + selectedText + end + oldText.substring(selectionEnd);
            textarea.focus(); // Keep focus
        }

        // 3. Image Handling
        function toggleImageInput(type) {
            const uploadDiv = document.getElementById('input-upload');
            const urlDiv = document.getElementById('input-url');
            const tabUp = document.getElementById('tab-upload');
            const tabUrl = document.getElementById('tab-url');

            if(type === 'upload') {
                uploadDiv.classList.remove('hidden');
                urlDiv.classList.add('hidden');
                document.getElementById('urlField').value = ''; // clear url
                
                tabUp.className = "flex-1 pb-2 text-sm font-bold text-brand-blue border-b-2 border-brand-blue";
                tabUrl.className = "flex-1 pb-2 text-sm font-bold text-gray-400";
            } else {
                uploadDiv.classList.add('hidden');
                urlDiv.classList.remove('hidden');
                
                tabUrl.className = "flex-1 pb-2 text-sm font-bold text-brand-blue border-b-2 border-brand-blue";
                tabUp.className = "flex-1 pb-2 text-sm font-bold text-gray-400";
            }
        }

        function previewFile(input) {
            const file = input.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview-box').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function clearImage() {
            document.getElementById('preview-box').classList.add('hidden');
            document.getElementById('preview-img').src = '';
            document.querySelector('input[name="image_file"]').value = '';
            document.getElementById('urlField').value = '';
        }
    </script>
    <?php endif; ?>
</body>
</html>