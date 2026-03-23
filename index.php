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

$res = $conn->query("SELECT 'sighting' as type, s.id, CONCAT(s.species_type,' sighting') as title, s.created_at, u.username
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
                <a href="<?= SITE_URL ?>/auth/register.php" class="btn-gold text-lg px-8 py-3">Join the Fleet</a>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn-outline text-lg px-8 py-3">Sign In</a>
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
    var ctx      = canvas.getContext('2d');
    var ripples  = [];
    var frame    = 0;
    var lastRippleMs = 0;
    var RIPPLE_COLOR_PRIMARY = '201,162,39';   // brand gold
    var RIPPLE_COLOR_ACCENT  = '255,215,80';   // lighter gold shimmer

    function resize() {
        var w = hero.offsetWidth;
        var h = hero.offsetHeight;
        if (w > 0 && h > 0) { canvas.width = w; canvas.height = h; }
    }
    window.addEventListener('resize', resize);
    resize();

    hero.addEventListener('mousemove', function (e) {
        var now = Date.now();
        if (now - lastRippleMs < 60) return;   // 60 ms throttle (~16.67 ripples/s max)
        lastRippleMs = now;
        var rect = canvas.getBoundingClientRect();
        ripples.push({ x: e.clientX - rect.left, y: e.clientY - rect.top, r: 0, o: 0.65 });
        if (ripples.length > 30) ripples.shift();
    });

    function draw() {
        var w = canvas.width;
        var h = canvas.height;
        if (w === 0 || h === 0) { resize(); requestAnimationFrame(draw); return; }

        ctx.clearRect(0, 0, w, h);

        // Ocean gradient background
        var bg = ctx.createLinearGradient(0, 0, 0, h);
        bg.addColorStop(0,    '#0d1f3c');
        bg.addColorStop(0.4,  '#112844');
        bg.addColorStop(0.75, '#1a3a5c');
        bg.addColorStop(1,    '#0a1628');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, w, h);

        // Animated wave layers
        var t = frame * 0.016;
        var layers = [
            { a: 30, f: 0.004, p: 0,   spd: 0.4, base: 0.82, col: 'rgba(15,45,90,0.55)' },
            { a: 22, f: 0.006, p: 1.2, spd: 0.6, base: 0.76, col: 'rgba(20,58,105,0.45)' },
            { a: 18, f: 0.008, p: 2.5, spd: 0.9, base: 0.70, col: 'rgba(28,70,125,0.38)' },
            { a: 14, f: 0.010, p: 4.0, spd: 1.3, base: 0.64, col: 'rgba(35,85,145,0.30)' },
            { a: 10, f: 0.013, p: 0.7, spd: 1.8, base: 0.58, col: 'rgba(45,100,160,0.22)' },
            { a:  8, f: 0.015, p: 3.3, spd: 2.2, base: 0.55, col: 'rgba(201,162,39,0.07)' },  // gold shimmer
        ];
        layers.forEach(function (wv) {
            var baseY = wv.base * h;
            ctx.beginPath();
            for (var x = 0; x <= w + 3; x += 3) {
                var y = baseY
                    + Math.sin(x * wv.f + t * wv.spd + wv.p) * wv.a
                    + Math.sin(x * wv.f * 0.5 + t * wv.spd * 0.7 + wv.p + 1) * wv.a * 0.35;
                if (x === 0) { ctx.moveTo(0, h); ctx.lineTo(0, y); }
                else ctx.lineTo(x, y);
            }
            ctx.lineTo(w, h);
            ctx.closePath();
            ctx.fillStyle = wv.col;
            ctx.fill();
        });

        // Mouse ripples
        for (var i = ripples.length - 1; i >= 0; i--) {
            var rip = ripples[i];
            ctx.beginPath();
            ctx.arc(rip.x, rip.y, rip.r, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(' + RIPPLE_COLOR_PRIMARY + ',' + rip.o.toFixed(3) + ')';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            if (rip.r > 12) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.55, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(' + RIPPLE_COLOR_PRIMARY + ',' + (rip.o * 0.6).toFixed(3) + ')';
                ctx.lineWidth = 1;
                ctx.stroke();
            }
            if (rip.r > 24) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.25, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(' + RIPPLE_COLOR_ACCENT + ',' + (rip.o * 0.35).toFixed(3) + ')';
                ctx.lineWidth = 0.75;
                ctx.stroke();
            }
            rip.r += 2.8;
            rip.o -= 0.013;
            if (rip.o <= 0 || rip.r > 90) ripples.splice(i, 1);
        }

        frame++;
        requestAnimationFrame(draw);
    }
    draw();
}());
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
