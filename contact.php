<?php
$msg = "";
if(isset($_POST['send'])) {
    // In real server, use mail() function here. For demo, we show success.
    $msg = "✅ Message sent successfully! We will contact you soon.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RajTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { blue: '#2563EB', dark: '#0F172A' } } } } }</script>
</head>
<body class="bg-gray-50 text-slate-800 antialiased flex flex-col min-h-screen">

    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100">
        <div class="container mx-auto px-4 h-20 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold tracking-tight text-brand-dark flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-brand-blue text-white flex items-center justify-center text-lg"><i class="fa-solid fa-code"></i></span> RajTech
            </a>
            <a href="index.php" class="text-sm font-bold text-gray-500 hover:text-brand-blue flex items-center gap-2"><i class="fa-solid fa-arrow-left"></i> Home</a>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-16 max-w-2xl">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-brand-dark mb-4">Get in Touch</h1>
            <p class="text-gray-500">Have a project in mind or want to say hi? We'd love to hear from you.</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <?php if($msg): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 text-center font-bold border border-green-200">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Name</label>
                        <input type="text" name="name" class="w-full bg-gray-50 border border-gray-200 p-3 rounded-xl focus:border-brand-blue focus:ring-brand-blue/20 outline-none transition" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Email</label>
                        <input type="email" name="email" class="w-full bg-gray-50 border border-gray-200 p-3 rounded-xl focus:border-brand-blue focus:ring-brand-blue/20 outline-none transition" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Subject</label>
                    <input type="text" name="subject" class="w-full bg-gray-50 border border-gray-200 p-3 rounded-xl focus:border-brand-blue focus:ring-brand-blue/20 outline-none transition" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Message</label>
                    <textarea name="message" rows="5" class="w-full bg-gray-50 border border-gray-200 p-3 rounded-xl focus:border-brand-blue focus:ring-brand-blue/20 outline-none transition" required></textarea>
                </div>
                <button type="submit" name="send" class="w-full bg-brand-blue text-white font-bold py-4 rounded-xl hover:bg-blue-600 transition shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-100 text-center">
                <p class="text-gray-500 text-sm">Or email us directly at</p>
                <a href="mailto:support@rajtech.com" class="text-brand-blue font-bold text-lg hover:underline">support@rajtech.com</a>
            </div>
        </div>
    </main>

    <footer class="bg-brand-dark text-white py-8 text-center text-sm border-t border-white/10 mt-auto">
        <p>&copy; 2026 RajTech. All rights reserved.</p>
    </footer>
</body>
</html>