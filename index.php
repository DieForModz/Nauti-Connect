<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Dashboard';

// Fetch stats
$stats = [];
foreach (['users','anchorages','sightings','parts_listings'] as $tbl) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM `$tbl`");
    $stats[$tbl] = (int)($r->fetch_assoc()['cnt'] ?? 0);
}

// Recent activity: combine anchorages, sightings, parts
$activity = [];

$res = $conn->query("SELECT 'anchorage' as type, a.id, a.name as title, a.created_at, u.username
    FROM anchorages a JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) $activity[] = $row;

$res = $conn->query("SELECT 'sighting' as type, s.id, CONCAT(s.sighting_type,' sighting') as title, s.created_at, u.username
    FROM sightings s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) $activity[] = $row;

$res = $conn->query("SELECT 'part' as type, p.id, p.title, p.created_at, u.username
    FROM parts_listings p JOIN users u ON u.id = p.seller_id ORDER BY p.created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) $activity[] = $row;

usort($activity, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$activity = array_slice($activity, 0, 10);

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section id="hero-section" class="relative overflow-hidden py-20 px-4">
    <canvas id="ocean-bg" class="ocean-canvas" aria-hidden="true"></canvas>

    <div class="relative z-10 max-w-7xl mx-auto text-center">
        <!-- Yacht SVG silhouette -->
        <div class="flex justify-center mb-8">
            <svg class="w-32 h-32 text-[#c9a227] opacity-90" viewBox="0 0 120 120" fill="currentColor" aria-label="Sailing yacht">
                <path d="M60 5 L60 80 L15 80 Z" fill="currentColor" opacity="0.9"/>
                <path d="M62 15 L62 80 L100 80 Z" fill="currentColor" opacity="0.6"/>
                <rect x="58" y="4" width="4" height="80" rx="2"/>
                <path d="M10 85 Q60 75 110 85 L115 95 Q60 88 5 95 Z" fill="currentColor" opacity="0.8"/>
                <path d="M20 98 Q60 92 100 98 L98 105 Q60 100 22 105 Z" fill="currentColor" opacity="0.5"/>
                <rect x="30" y="80" width="60" height="5" rx="2.5"/>
                <rect x="55" y="80" width="10" height="20" rx="2"/>
            </svg>
        </div>

        <h1 class="text-4xl sm:text-6xl font-extrabold text-white mb-4">
            Navigate Together
        </h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto mb-8">
            The complete platform for boaters — find anchorages, track wildlife, buy &amp; sell gear, and connect with fellow mariners.
        </p>

        <div class="flex flex-wrap gap-4 justify-center mb-12">
            <?php if (!isLoggedIn()): ?>
                <button type="button" class="btn-gold text-lg px-8 py-3" data-open-modal="register">Join the Fleet</button>
                <button type="button" class="btn-outline text-lg px-8 py-3" data-open-modal="login">Sign In</button>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/anchorages/add.php" class="btn-gold text-lg px-8 py-3">Add Anchorage</a>
                <a href="<?= SITE_URL ?>/sightings/report.php" class="btn-outline text-lg px-8 py-3">Report Sighting</a>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
            <div class="glass-card p-4 text-center">
                <div class="text-3xl font-bold text-[#c9a227]"><?= number_format($stats['users']) ?></div>
                <div class="text-gray-400 text-sm mt-1">Mariners</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-3xl font-bold text-[#c9a227]"><?= number_format($stats['anchorages']) ?></div>
                <div class="text-gray-400 text-sm mt-1">Anchorages</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-3xl font-bold text-[#c9a227]"><?= number_format($stats['sightings']) ?></div>
                <div class="text-gray-400 text-sm mt-1">Sightings</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-3xl font-bold text-[#c9a227]"><?= number_format($stats['parts_listings']) ?></div>
                <div class="text-gray-400 text-sm mt-1">Parts Listed</div>
            </div>
        </div>
    </div>
</section>

<!-- Wave divider -->
<svg viewBox="0 0 1440 60" fill="none" class="w-full -mt-1" aria-hidden="true">
    <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#0f2340"/>
</svg>

<!-- Map Section -->
<section class="bg-[#0f2340] py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <h2 class="text-2xl font-bold text-white">Live Community Map</h2>
            <div class="flex flex-wrap gap-2">
                <button onclick="filterMarkers('all')" class="filter-btn active" data-filter="all">All</button>
                <button onclick="filterMarkers('anchorage')" class="filter-btn" data-filter="anchorage">
                    <span class="w-2 h-2 bg-blue-400 rounded-full inline-block mr-1"></span>Anchorages
                </button>
                <button onclick="filterMarkers('sighting')" class="filter-btn" data-filter="sighting">
                    <span class="w-2 h-2 bg-orange-400 rounded-full inline-block mr-1"></span>Sightings
                </button>
                <button onclick="filterMarkers('user')" class="filter-btn" data-filter="user">
                    <span class="w-2 h-2 bg-green-400 rounded-full inline-block mr-1"></span>Boaters
                </button>
            </div>
        </div>
        <div id="main-map" class="map-container rounded-xl overflow-hidden border border-[#c9a227]/20"></div>
        <p class="text-gray-500 text-xs mt-2 text-center">Click the map to add an anchorage or sighting</p>
    </div>
</section>

<!-- Wave divider -->
<svg viewBox="0 0 1440 60" fill="none" class="w-full" aria-hidden="true">
    <path d="M0,30 C360,0 1080,60 1440,30 L1440,0 L0,0 Z" fill="#0f2340"/>
</svg>

<!-- Quick Features Grid -->
<section class="py-16 px-4 bg-[#0a1628]">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-white mb-12">Everything You Need on the Water</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php
            $features = [
                ['icon'=>'⚓','title'=>'Anchorages','desc'=>'Discover and share the best anchorages with depth, holding quality, and protection ratings.','link'=>'/anchorages/','cta'=>'Explore Anchorages'],
                ['icon'=>'🐬','title'=>'Wildlife Sightings','desc'=>'Report and track orca, dolphin, seal, and whale sightings in real-time with photo uploads.','link'=>'/sightings/','cta'=>'View Sightings'],
                ['icon'=>'🔧','title'=>'Parts & Gear','desc'=>'Buy and sell marine parts, equipment, and gear from fellow boaters.','link'=>'/parts/','cta'=>'Browse Parts'],
                ['icon'=>'⛵','title'=>'Boats for Sale','desc'=>'Find your next vessel or list yours. Full specs, photos, and direct messaging.','link'=>'/boats/','cta'=>'Browse Boats'],
                ['icon'=>'🗺️','title'=>'Chart Library','desc'=>'Download GPX and KMZ charts shared by the community for every ocean region.','link'=>'/charts/','cta'=>'Get Charts'],
                ['icon'=>'🔩','title'=>'Build Logs','desc'=>'Document your boat build or refit with photos, progress bars, and timeline entries.','link'=>'/builds/','cta'=>'View Builds'],
                ['icon'=>'📋','title'=>'Maintenance Guides','desc'=>'Step-by-step guides for engine winterization, fiberglass repair, rigging, and more.','link'=>'/maintenance/','cta'=>'Read Guides'],
                ['icon'=>'🤖','title'=>'AI Maintenance Assistant','desc'=>'Ask our AI for maintenance advice, powered by our curated guide library.','link'=>'/maintenance/','cta'=>'Ask the AI'],
                ['icon'=>'💬','title'=>'Direct Messaging','desc'=>'Connect directly with sellers and fellow boaters about listings or the community.','link'=>'/messages/','cta'=>'Open Messages'],
            ];
            foreach ($features as $f): ?>
                <a href="<?= SITE_URL . $f['link'] ?>" class="glass-card p-6 hover:border-[#c9a227]/60 transition-all group block">
                    <div class="text-4xl mb-3"><?= $f['icon'] ?></div>
                    <h3 class="text-xl font-bold text-white mb-2 group-hover:text-[#c9a227] transition-colors"><?= $f['title'] ?></h3>
                    <p class="text-gray-400 text-sm mb-4 leading-relaxed"><?= $f['desc'] ?></p>
                    <span class="text-[#c9a227] text-sm font-semibold"><?= $f['cta'] ?> →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Recent Activity -->
<?php if (!empty($activity)): ?>
<section class="py-12 px-4 bg-[#0f2340]">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6">Recent Activity</h2>
        <div class="space-y-3">
            <?php foreach ($activity as $item): ?>
                <?php
                $icon  = match ($item['type']) { 'anchorage' => '⚓', 'sighting' => '🐬', 'part' => '🔧', default => '📌' };
                $link  = match ($item['type']) {
                    'anchorage' => SITE_URL . '/anchorages/view.php?id=' . $item['id'],
                    'sighting'  => SITE_URL . '/sightings/view.php?id=' . $item['id'],
                    'part'      => SITE_URL . '/parts/view.php?id=' . $item['id'],
                    default     => '#',
                };
                ?>
                <a href="<?= $link ?>" class="flex items-center gap-4 glass-card p-4 hover:border-[#c9a227]/50 transition-all">
                    <span class="text-2xl"><?= $icon ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium truncate"><?= sanitize($item['title']) ?></p>
                        <p class="text-gray-400 text-sm">by <span class="text-[#c9a227]"><?= sanitize($item['username']) ?></span></p>
                    </div>
                    <span class="text-gray-500 text-xs whitespace-nowrap"><?= timeAgo($item['created_at']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Add pin modal -->
<div id="map-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="glass-card p-6 max-w-sm w-full">
        <h3 class="text-xl font-bold mb-4">Add to Map</h3>
        <p class="text-gray-400 mb-4 text-sm">What would you like to add at this location?</p>
        <div class="grid grid-cols-2 gap-3">
            <a id="modal-anchorage-link" href="#" class="btn-gold text-center py-2">⚓ Anchorage</a>
            <a id="modal-sighting-link" href="#" class="btn-outline text-center py-2">🐬 Sighting</a>
        </div>
        <button onclick="document.getElementById('map-modal').classList.add('hidden')" class="mt-4 w-full text-gray-400 hover:text-white text-sm">Cancel</button>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
    initMap('main-map', {
        lat: 20, lng: 0, zoom: 2,
        dataUrl: '<?= SITE_URL ?>/api/map-data.php',
        addPinCallback: function(lat, lng) {
            document.getElementById('modal-anchorage-link').href =
                '<?= SITE_URL ?>/anchorages/add.php?lat=' + lat + '&lng=' + lng;
            document.getElementById('modal-sighting-link').href =
                '<?= SITE_URL ?>/sightings/report.php?lat=' + lat + '&lng=' + lng;
            document.getElementById('map-modal').classList.remove('hidden');
        }
    });
</script>

<!-- Ocean background animation -->
<script>
(function () {
    var hero   = document.getElementById('hero-section');
    var canvas = document.getElementById('ocean-bg');
    if (!canvas || !hero) return;
    var ctx = canvas.getContext('2d');

    var ripples  = [];
    var foam     = [];
    var sparkles = [];
    var frame    = 0;
    var lastRippleMs  = 0;
    var resizeTimer   = null;
    // Horizontal position (0–1) of the light-source used for ray/haze rendering
    var SUN_POS_RATIO = 0.62;
    // Respawned foam starts within the leftmost fraction of the canvas so it
    // drifts rightward across the visible surface over its lifetime
    var FOAM_SPAWN_WIDTH_RATIO = 0.4;

    // Wave layer definitions shared across draw passes
    var WAVE_LAYERS = [
        { a: 38, f: 0.0038, p: 0.0, spd: 0.32, base: 0.82, col: 'rgba(8,42,85,0.62)'   },
        { a: 28, f: 0.0058, p: 1.2, spd: 0.52, base: 0.76, col: 'rgba(10,52,100,0.52)'  },
        { a: 22, f: 0.0078, p: 2.5, spd: 0.76, base: 0.70, col: 'rgba(14,64,118,0.44)'  },
        { a: 17, f: 0.0105, p: 4.0, spd: 1.15, base: 0.65, col: 'rgba(18,76,135,0.36)'  },
        { a: 13, f: 0.0135, p: 0.7, spd: 1.60, base: 0.60, col: 'rgba(25,90,155,0.28)'  },
        { a:  9, f: 0.0165, p: 3.3, spd: 2.10, base: 0.56, col: 'rgba(34,108,172,0.20)' },
    ];

    function waveY(x, wv, t) {
        return wv.base * (canvas.height || 400)
            + Math.sin(x * wv.f + t * wv.spd + wv.p) * wv.a
            + Math.sin(x * wv.f * 0.47 + t * wv.spd * 0.68 + wv.p + 1.1) * wv.a * 0.38;
    }

    function resize() {
        var w = hero.offsetWidth;
        var h = hero.offsetHeight;
        if (w > 0 && h > 0) { canvas.width = w; canvas.height = h; }
        initParticles();
    }
    // Debounce resize so rapid window-drag events don't churn all particles
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resize, 120);
    });
    resize();

    // Spawn a foam particle
    function newFoam() {
        var w = canvas.width || 800;
        return {
            x:  Math.random() * w,
            y:  0,           // positioned to wave surface in draw()
            r:  0.8 + Math.random() * 2.5,
            o:  0.12 + Math.random() * 0.25,
            dx: 0.25 + Math.random() * 0.65,
            life: Math.random(),
            waveIdx: 1 + Math.floor(Math.random() * 3)
        };
    }

    // Spawn a sun-glint sparkle
    function newSparkle() {
        var w = canvas.width || 800;
        var h = canvas.height || 400;
        return {
            x: Math.random() * w,
            y: h * 0.28 + Math.random() * h * 0.45,
            size: 0.8 + Math.random() * 2.0,
            life: 0,
            maxLife: 0.6 + Math.random() * 1.8
        };
    }

    function initParticles() {
        foam = [];
        sparkles = [];
        for (var i = 0; i < 45; i++) foam.push(newFoam());
        for (var j = 0; j < 22; j++) sparkles.push(newSparkle());
    }

    // Mouse ripple — continuous as cursor moves
    hero.addEventListener('mousemove', function (e) {
        var now = Date.now();
        if (now - lastRippleMs < 70) return;
        lastRippleMs = now;
        var rect = canvas.getBoundingClientRect();
        ripples.push({
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
            r: 0,
            o: 0.75,
            spd: 1.8 + Math.random() * 1.6
        });
        if (ripples.length > 28) ripples.shift();
    });

    function draw() {
        var w = canvas.width;
        var h = canvas.height;
        if (w === 0 || h === 0) { resize(); requestAnimationFrame(draw); return; }

        ctx.clearRect(0, 0, w, h);

        /* ── Deep-water sky-to-ocean gradient ─────────────────────── */
        var bg = ctx.createLinearGradient(0, 0, 0, h);
        bg.addColorStop(0,    '#07182e');   // night-sky navy
        bg.addColorStop(0.18, '#0b2540');   // horizon indigo
        bg.addColorStop(0.40, '#0e3560');   // twilight ocean
        bg.addColorStop(0.65, '#0b4070');   // near-surface teal-blue
        bg.addColorStop(0.85, '#093258');   // mid-depth
        bg.addColorStop(1,    '#060f20');   // abyssal
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, w, h);

        /* ── Subtle moonlight / sun-haze rays ──────────────────────── */
        var sunX = w * SUN_POS_RATIO;
        for (var s = 0; s < 7; s++) {
            var spread = (s - 3) * 0.055;
            var rayGrad = ctx.createLinearGradient(sunX, 0, sunX + Math.sin(spread) * h * 1.2, h);
            rayGrad.addColorStop(0, 'rgba(160,210,255,0.04)');
            rayGrad.addColorStop(1, 'rgba(160,210,255,0)');
            ctx.beginPath();
            ctx.moveTo(sunX + (s - 3) * 10,     0);
            ctx.lineTo(sunX + Math.sin(spread) * h * 1.2 + 20, h);
            ctx.lineTo(sunX + Math.sin(spread) * h * 1.2 - 20, h);
            ctx.lineTo(sunX + (s - 3) * 10 + 8, 0);
            ctx.closePath();
            ctx.fillStyle = rayGrad;
            ctx.fill();
        }

        /* ── Animated wave fill layers ─────────────────────────────── */
        var t = frame * 0.016;
        WAVE_LAYERS.forEach(function (wv) {
            ctx.beginPath();
            for (var x = 0; x <= w + 3; x += 3) {
                var y = waveY(x, wv, t);
                if (x === 0) { ctx.moveTo(0, h); ctx.lineTo(0, y); }
                else ctx.lineTo(x, y);
            }
            ctx.lineTo(w, h);
            ctx.closePath();
            ctx.fillStyle = wv.col;
            ctx.fill();
        });

        /* ── Wave-crest highlights (thin bright lines) ─────────────── */
        for (var li = 0; li < WAVE_LAYERS.length - 1; li++) {
            var wv = WAVE_LAYERS[li];
            ctx.beginPath();
            for (var x = 0; x <= w + 2; x += 2) {
                var y = waveY(x, wv, t);
                if (x === 0) ctx.moveTo(0, y); else ctx.lineTo(x, y);
            }
            ctx.strokeStyle = 'rgba(130,195,255,0.07)';
            ctx.lineWidth = 1.2;
            ctx.stroke();
        }

        /* ── Foam / sea-spray particles ────────────────────────────── */
        for (var fi = 0; fi < foam.length; fi++) {
            var f = foam[fi];
            var wv = WAVE_LAYERS[f.waveIdx];
            f.x   += f.dx;
            f.life += 0.006;
            var surfaceY = waveY(f.x, wv, t);
            if (f.life > 1 || f.x > w + 10) {
                foam[fi] = newFoam();
                foam[fi].x = Math.random() * w * FOAM_SPAWN_WIDTH_RATIO;
            } else {
                ctx.beginPath();
                ctx.arc(f.x, surfaceY - 1 + (Math.random() - 0.5) * 4, f.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(195,228,255,' + (f.o * (1 - f.life * 0.6)).toFixed(3) + ')';
                ctx.fill();
            }
        }

        /* ── Sun-glint sparkles ─────────────────────────────────────── */
        for (var si = 0; si < sparkles.length; si++) {
            var sp = sparkles[si];
            sp.life += 0.018;
            if (sp.life > sp.maxLife) { sparkles[si] = newSparkle(); continue; }
            var progress = sp.life / sp.maxLife;
            var o = Math.sin(progress * Math.PI) * 0.65;
            // Core dot
            ctx.beginPath();
            ctx.arc(sp.x, sp.y, sp.size, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(215,240,255,' + o.toFixed(3) + ')';
            ctx.fill();
            // Cross flare
            var arm = sp.size * 3.5;
            ctx.beginPath();
            ctx.moveTo(sp.x - arm, sp.y); ctx.lineTo(sp.x + arm, sp.y);
            ctx.moveTo(sp.x, sp.y - arm); ctx.lineTo(sp.x, sp.y + arm);
            ctx.strokeStyle = 'rgba(215,240,255,' + (o * 0.45).toFixed(3) + ')';
            ctx.lineWidth = 0.6;
            ctx.stroke();
        }

        /* ── Mouse-cursor water ripples ─────────────────────────────── */
        for (var i = ripples.length - 1; i >= 0; i--) {
            var rip = ripples[i];
            // Outer ring
            ctx.beginPath();
            ctx.arc(rip.x, rip.y, rip.r, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(140,210,255,' + rip.o.toFixed(3) + ')';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            // Mid ring
            if (rip.r > 14) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.58, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(175,225,255,' + (rip.o * 0.55).toFixed(3) + ')';
                ctx.lineWidth = 1.0;
                ctx.stroke();
            }
            // Inner ring
            if (rip.r > 28) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.28, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(220,242,255,' + (rip.o * 0.30).toFixed(3) + ')';
                ctx.lineWidth = 0.7;
                ctx.stroke();
            }
            rip.r += rip.spd;
            rip.o -= 0.011;
            if (rip.o <= 0 || rip.r > 105) ripples.splice(i, 1);
        }

        frame++;
        requestAnimationFrame(draw);
    }
    draw();
}());
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
