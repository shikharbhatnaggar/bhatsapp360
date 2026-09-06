<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Bhatsapp'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Instrument Sans"', 'system-ui', 'sans-serif'] },
                    colors: {
                        ink:    { 50:'#F1F4F2', 100:'#E4EAE7', 200:'#DCE4E1', 300:'#A9B8B3', 500:'#5A706A', 700:'#1E332E', 900:'#0C1B18' },
                        jade:   { 50:'#E7F3EF', 200:'#B7DACE', 400:'#3E9B80', 600:'#17755E', 700:'#14624F', 900:'#0B3A2F' },
                        signal: { 50:'#FBF2E2', 200:'#EFD8A8', 600:'#B57614', 700:'#8A5A10' },
                        alert:  { 50:'#FBEDEB', 200:'#EDC4BF', 600:'#B23A2F', 700:'#8F2F27' },
                        paper:  '#F8F9F8',
                    },
                    boxShadow: { lift: '0 1px 2px rgba(12,27,24,.06), 0 8px 24px -12px rgba(12,27,24,.18)' },
                },
            },
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .num { font-variant-numeric: tabular-nums; }
        .chat-canvas { background-color:#E9E3DB; background-image:radial-gradient(rgba(12,27,24,.05) 1px, transparent 1px); background-size:14px 14px; }
        [x-cloak] { display: none !important; }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="h-full bg-paper text-ink-900 font-sans">
    <?php echo $__env->yieldContent('body'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\projects\bhatsapps\resources\views/layouts/base.blade.php ENDPATH**/ ?>