<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> · SIM Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/app.css'))); ?>">
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/app.js'))); ?>" defer></script>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>" alt="Logo Puskesmas Akelamo">
            <div>
                <b>Puskesmas Akelamo</b>
                <small>SMART HEALTH SYSTEM</small>
            </div>
        </div>
        <div class="nav-title">MENU UTAMA</div>
        <nav>
            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="nav-item <?php echo e(($crumb ?? '') === $key ? 'active' : ''); ?>" href="<?php echo e(\App\Support\ModuleRegistry::urlFor($key)); ?>">
                    <span><?php echo e($item[0]); ?></span><?php echo e($item[1]); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
        <div class="sidebar-bottom">
            © <?php echo e(date('Y')); ?> Puskesmas Akelamo<br>Sistem Informasi Terintegrasi
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" id="mobile-toggle" aria-label="Buka menu">☰</button>
                <div class="crumb">Menu / <strong><?php echo e($pageTitle ?? 'Dashboard'); ?></strong></div>
            </div>
            <div class="profile">
                <div class="notif-wrap">
                    <button class="notif-bell" id="notif-bell" type="button" aria-label="Notifikasi">🔔
                        <?php ($unreadCount = \App\Models\AppNotification::unread()->count()); ?>
                        <?php if($unreadCount > 0): ?><span class="notif-badge" id="notif-badge"><?php echo e($unreadCount > 99 ? '99+' : $unreadCount); ?></span><?php endif; ?>
                    </button>
                    <div class="notif-panel" id="notif-panel" hidden></div>
                </div>
                <a class="profile-link" href="<?php echo e(url('/profil')); ?>" title="Profil & ganti sandi">
                    <div class="avatar"><?php echo e(mb_strtoupper(mb_substr(auth()->user()->full_name, 0, 1))); ?></div>
                    <div>
                        <b><?php echo e(auth()->user()->full_name); ?></b>
                        <small><?php echo e(auth()->user()->role); ?></small>
                    </div>
                </a>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="logout" type="submit" title="Keluar">↪</button>
                </form>
            </div>
        </header>
        <div class="content">
            <?php if(session('status')): ?>
                <div class="flash-ok"><?php echo e(session('status')); ?></div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
        <footer class="app-footer">
            <span>© <?php echo e(date('Y')); ?> Puskesmas Akelamo — Sistem Informasi Manajemen Puskesmas.</span>
            <span>
                Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
            </span>
        </footer>
    </main>
</div>
<div id="toast" role="status" aria-live="polite"></div>
</body>
</html>
<?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/layouts/app.blade.php ENDPATH**/ ?>