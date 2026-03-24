<!-- Wave divider -->
<div class="wave-container">
    <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="rgba(5,30,65,0.88)"/>
    </svg>
</div>

<footer style="background:rgba(5,30,65,0.92);" class="border-t border-[#c9a227]/20 pt-12 pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
            <!-- Brand -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-8 h-8 text-[#c9a227]" viewBox="0 0 40 40" fill="currentColor" aria-hidden="true">
                        <path d="M20 2L4 32h32L20 2zm0 6l11 20H9L20 8z"/>
                        <rect x="10" y="34" width="20" height="3" rx="1.5"/>
                    </svg>
                    <span class="text-xl font-bold text-[#c9a227]">Nauti-Connect</span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">The community platform for boaters. Share, explore, and connect on the water.</p>
            </div>

            <!-- Navigate -->
            <div>
                <h4 class="text-[#c9a227] font-semibold mb-3">Explore</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="<?= SITE_URL ?>/anchorages/" class="hover:text-[#c9a227] transition-colors">Anchorages</a></li>
                    <li><a href="<?= SITE_URL ?>/sightings/" class="hover:text-[#c9a227] transition-colors">Wildlife Sightings</a></li>
                    <li><a href="<?= SITE_URL ?>/charts/" class="hover:text-[#c9a227] transition-colors">Chart Library</a></li>
                    <li><a href="<?= SITE_URL ?>/builds/" class="hover:text-[#c9a227] transition-colors">Build Logs</a></li>
                </ul>
            </div>

            <!-- Marketplace -->
            <div>
                <h4 class="text-[#c9a227] font-semibold mb-3">Marketplace</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="<?= SITE_URL ?>/parts/" class="hover:text-[#c9a227] transition-colors">Parts &amp; Gear</a></li>
                    <li><a href="<?= SITE_URL ?>/boats/" class="hover:text-[#c9a227] transition-colors">Boats for Sale</a></li>
                    <li><a href="<?= SITE_URL ?>/parts/sell.php" class="hover:text-[#c9a227] transition-colors">Sell a Part</a></li>
                    <li><a href="<?= SITE_URL ?>/boats/sell.php" class="hover:text-[#c9a227] transition-colors">List Your Boat</a></li>
                </ul>
            </div>

            <!-- Community -->
            <div>
                <h4 class="text-[#c9a227] font-semibold mb-3">Community</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="<?= SITE_URL ?>/maintenance/" class="hover:text-[#c9a227] transition-colors">Maintenance Guides</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php" class="hover:text-[#c9a227] transition-colors">Join the Network</a></li>
                    <li><a href="<?= SITE_URL ?>/profile/" class="hover:text-[#c9a227] transition-colors">My Profile</a></li>
                    <li><a href="<?= SITE_URL ?>/messages/" class="hover:text-[#c9a227] transition-colors">Messages</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[#c9a227]/20 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> Nauti-Connect. All rights reserved.</p>
            <p class="text-gray-600 text-xs">Built for the boating community &#9875;</p>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="<?= SITE_URL ?>/assets/js/app.js"></script>
</body>
</html>
