<?php $__env->startSection('content'); ?>
<div class="hero hero-animated">
    <div class="hero-inner">
        <span class="eyebrow">SELAMAT DATANG DI SIM AKELAMO</span>
        <h1>Pelayanan Kesehatan<br>Lebih Mudah &amp; Terintegrasi</h1>
        <p>Kelola layanan pasien, tenaga kesehatan, obat, keuangan, dan survei masyarakat dalam satu sistem informasi terpadu.</p>
    </div>
    <div class="hero-badge">☀ <?php echo e(now()->locale('id')->translatedFormat('l, j F Y')); ?></div>
</div>

<div class="section-head anim-up">
    <div>
        <h2>Ringkasan Operasional</h2>
        <p>Informasi sesuai hak akses akun <?php echo e(auth()->user()->role); ?> — arahkan kursor pada kartu, klik untuk membuka detail.</p>
    </div>
    <span class="tag tag-pulse">● Terhubung ke Database</span>
</div>

<div class="stats">
    <?php $__empty_1 = true; $__currentLoopData = $tiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $tile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php if($tile['link']): ?>
            <a class="stat stat-animated stat-link" href="<?php echo e($tile['link']); ?>" style="--delay: <?php echo e($i * 0.09); ?>s">
        <?php else: ?>
            <div class="stat stat-animated" style="--delay: <?php echo e($i * 0.09); ?>s">
        <?php endif; ?>
            <div class="icon-tile"><?php echo e($tile['icon']); ?></div>
            <small><?php echo e($tile['title']); ?></small>
            <strong class="count-up"
                    data-count="<?php echo e($tile['value']); ?>"
                    data-format="<?php echo e($tile['format']); ?>">0</strong>
            <em><?php echo e($tile['sub']); ?></em>
            <?php if($tile['link']): ?><span class="stat-go">Lihat detail →</span><?php endif; ?>
        <?php if($tile['link']): ?></a><?php else: ?></div><?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="stat"><small>Tidak ada statistik untuk peran ini.</small></div>
    <?php endif; ?>
</div>


<div class="chart-grid">
    <?php if(isset($barChart['finance'])): ?>
        <section class="panel chart-panel anim-up" style="--delay:.2s">
            <div class="chart-head">
                <h3>📊 <?php echo e($barChart['finance']['title']); ?></h3>
                <div class="chart-legend">
                    <span><i class="dot dot-in"></i><?php echo e($barChart['finance']['inLabel']); ?></span>
                    <span><i class="dot dot-out"></i><?php echo e($barChart['finance']['outLabel']); ?></span>
                </div>
            </div>
            <div class="bar-chart bar-chart-dual"
                 data-labels="<?php echo e(json_encode($barChart['finance']['labels'])); ?>"
                 data-series='<?php echo json_encode([$barChart['finance']['in'], $barChart['finance']['out']]); ?>'
                 data-format="currency"></div>
        </section>
    <?php endif; ?>

    <?php if(isset($barChart['patients'])): ?>
        <section class="panel chart-panel anim-up" style="--delay:.25s">
            <div class="chart-head"><h3>📊 <?php echo e($barChart['patients']['title']); ?></h3></div>
            <div class="bar-chart"
                 data-labels="<?php echo e(json_encode($barChart['patients']['labels'])); ?>"
                 data-series='<?php echo json_encode([$barChart['patients']['values']]); ?>'
                 data-format="number"></div>
        </section>
    <?php endif; ?>

    <?php if($venn): ?>
        <section class="panel chart-panel anim-up" style="--delay:.3s">
            <div class="chart-head"><h3>⭕ <?php echo e($venn['title']); ?></h3></div>
            <div class="venn-wrap">
                <svg viewBox="0 0 320 200" class="venn" role="img" aria-label="<?php echo e($venn['title']); ?>">
                    <circle class="venn-circle venn-a" cx="120" cy="100" r="78"/>
                    <circle class="venn-circle venn-b" cx="200" cy="100" r="78"/>
                    <text class="venn-num" x="80" y="92" text-anchor="middle" data-count="<?php echo e($venn['a'] - $venn['both']); ?>">0</text>
                    <text class="venn-num venn-num-both" x="160" y="92" text-anchor="middle" data-count="<?php echo e($venn['both']); ?>">0</text>
                    <text class="venn-num" x="240" y="92" text-anchor="middle" data-count="<?php echo e($venn['b'] - $venn['both']); ?>">0</text>
                    <text class="venn-cap" x="52" y="128" text-anchor="middle"><?php echo e($venn['aLabel']); ?></text>
                    <text class="venn-cap venn-cap-both" x="160" y="120" text-anchor="middle">Keduanya</text>
                    <text class="venn-cap" x="272" y="128" text-anchor="middle"><?php echo e($venn['bLabel']); ?></text>
                </svg>
            </div>
            <p class="muted venn-caption"><?php echo e($venn['caption']); ?></p>
        </section>
    <?php endif; ?>
</div>

<div class="panels">
    <section class="panel anim-up" style="--delay:.35s">
        <h3>⚡ Akses Cepat</h3>
        <div class="quick-links">
            <?php $__currentLoopData = $quickLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="quick" href="<?php echo e(\App\Support\ModuleRegistry::urlFor($key)); ?>">
                    <?php echo e($item[0]); ?> &nbsp;<?php echo e($item[1]); ?> &nbsp;→
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <section class="panel anim-up" style="--delay:.45s">
        <h3>📅 Jadwal Praktik Terbaru</h3>
        <?php $__empty_1 = true; $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="schedule-row">
                <b><?php echo e($s['doctor']); ?></b>
                <div class="muted"><?php echo e($s['day']); ?> · <?php echo e($s['time_start']); ?>–<?php echo e($s['time_end']); ?> · <?php echo e($s['room']); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="muted">Jadwal tidak tersedia pada akses ini atau belum diisi.</p>
        <?php endif; ?>
    </section>
</div>

<p class="footer-note">🔒 Akses berbasis peran. Data tersimpan di server dan tidak disimpan di localStorage perangkat.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'dashboard', 'pageTitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/dashboard.blade.php ENDPATH**/ ?>