/**
 * Nauti-Connect — Full-Page Realistic Ocean Background
 *
 * Renders a fixed, full-viewport canvas behind every page:
 *   • Daytime sky gradient with sun disc and light rays
 *   • Layered animated ocean waves (bright aqua / teal / cerulean)
 *   • Sunlight shimmer path across the water
 *   • Foam & sea-spray particles riding the wave crests
 *   • Sun-glint sparkle particles
 *   • Cursor ripple rings tracked across the entire viewport
 */
(function () {
    'use strict';

    var canvas = document.getElementById('nc-ocean-bg');
    if (!canvas) return;

    var ctx      = canvas.getContext('2d');
    var W        = 0;
    var H        = 0;
    var frame    = 0;
    var ripples  = [];
    var foam     = [];
    var sparkles = [];
    var lastMs   = 0;
    var resizeTmr = null;

    /* ── Sun horizontal position (0–1 fraction of canvas width)
     *  0.68 places the sun in the right-of-centre "golden hour" position
     *  so the shimmer path and light rays angle naturally left-to-right. ── */
    var SUN_X = 0.68;

    /* ── Wave layers (from deepest / lowest to shallowest / topmost) ─
     *  a    = amplitude in pixels
     *  f    = spatial frequency (cycles / pixel)
     *  p    = initial phase offset
     *  spd  = time-based speed multiplier
     *  base = vertical centre as fraction of canvas height
     *  col  = semi-transparent fill colour
     * ─────────────────────────────────────────────────────────────── */
    var WAVES = [
        { a: 36, f: 0.0030, p: 0.0, spd: 0.26, base: 0.84, col: 'rgba(12,  96, 168, 0.52)' },
        { a: 26, f: 0.0048, p: 1.4, spd: 0.44, base: 0.78, col: 'rgba(20, 122, 190, 0.43)' },
        { a: 18, f: 0.0068, p: 2.8, spd: 0.66, base: 0.72, col: 'rgba(30, 150, 208, 0.34)' },
        { a: 13, f: 0.0092, p: 4.2, spd: 1.00, base: 0.66, col: 'rgba(44, 172, 220, 0.26)' },
        { a:  8, f: 0.0120, p: 0.9, spd: 1.40, base: 0.61, col: 'rgba(62, 192, 230, 0.18)' },
        { a:  5, f: 0.0150, p: 3.6, spd: 1.85, base: 0.56, col: 'rgba(82, 210, 238, 0.12)' },
    ];

    /* ── Wave y-position helper ─────────────────────────────────── */
    function waveY(x, wv, t) {
        return wv.base * H
            + Math.sin(x * wv.f + t * wv.spd + wv.p)              * wv.a
            + Math.sin(x * wv.f * 0.47 + t * wv.spd * 0.65 + wv.p + 0.85) * wv.a * 0.36;
    }

    /* ── Particle factories ─────────────────────────────────────── */
    function newFoam() {
        return {
            x:    Math.random() * W,
            r:    0.7 + Math.random() * 2.4,
            o:    0.14 + Math.random() * 0.28,
            dx:   0.18 + Math.random() * 0.55,
            life: Math.random(),
            wi:   1 + Math.floor(Math.random() * 3),
        };
    }

    function newSparkle() {
        return {
            x:       W * 0.28 + Math.random() * W * 0.58,
            y:       H * 0.26 + Math.random() * H * 0.52,
            sz:      0.6 + Math.random() * 1.8,
            life:    0,
            maxLife: 0.8 + Math.random() * 2.2,
        };
    }

    function initParticles() {
        foam     = [];
        sparkles = [];
        /* Particle density: one foam particle per ~22 000 px² of canvas area,
         * capped at 55 so large monitors don't spawn excessive particles.        */
        var MAX_FOAM   = 55;
        var PX_PER_FOAM = 22000;
        var n = Math.min(MAX_FOAM, Math.floor((W * H) / PX_PER_FOAM));
        for (var i = 0; i < n; i++) foam.push(newFoam());
        for (var j = 0; j < 30; j++) sparkles.push(newSparkle());
    }

    /* ── Canvas resize (viewport-sized, fixed behind page) ───────── */
    function resize() {
        W             = window.innerWidth;
        H             = window.innerHeight;
        canvas.width  = W;
        canvas.height = H;
        initParticles();
    }

    window.addEventListener('resize', function () {
        clearTimeout(resizeTmr);
        resizeTmr = setTimeout(resize, 150);
    });

    resize();

    /* ── Cursor ripple tracking — entire document ─────────────── */
    document.addEventListener('mousemove', function (e) {
        var now = Date.now();
        if (now - lastMs < 55) return;
        lastMs = now;
        ripples.push({
            x:   e.clientX,
            y:   e.clientY,
            r:   0,
            o:   0.88,
            spd: 1.4 + Math.random() * 2.0,
        });
        if (ripples.length > 40) ripples.shift();
    });

    /* ── Main render loop ─────────────────────────────────────── */
    function draw() {
        if (W === 0 || H === 0) { resize(); requestAnimationFrame(draw); return; }

        ctx.clearRect(0, 0, W, H);

        /* ── Sky gradient (top ~30 % of canvas) ── */
        var skyH    = H * 0.30;
        var skyGrad = ctx.createLinearGradient(0, 0, 0, skyH);
        skyGrad.addColorStop(0,   '#C8EEFF');   // pale zenith
        skyGrad.addColorStop(0.4, '#82C8EE');   // mid-sky
        skyGrad.addColorStop(0.8, '#4AAEDE');   // lower sky
        skyGrad.addColorStop(1,   '#2E96CC');   // horizon blue
        ctx.fillStyle = skyGrad;
        ctx.fillRect(0, 0, W, skyH);

        /* ── Horizon glow (radial haze around sun) ── */
        var sunX = W * SUN_X;
        var sunY = skyH * 0.26;
        var hgr  = ctx.createRadialGradient(sunX, skyH, 0, sunX, skyH, W * 0.55);
        hgr.addColorStop(0,    'rgba(255, 225, 140, 0.30)');
        hgr.addColorStop(0.35, 'rgba(120, 200, 255, 0.18)');
        hgr.addColorStop(1,    'rgba(120, 200, 255, 0)');
        ctx.fillStyle = hgr;
        ctx.fillRect(0, skyH * 0.4, W, skyH);

        /* ── Sun disc ── */
        var sunR = ctx.createRadialGradient(sunX, sunY, 0, sunX, sunY, 52);
        sunR.addColorStop(0,    'rgba(255, 255, 230, 0.96)');
        sunR.addColorStop(0.35, 'rgba(255, 235, 140, 0.62)');
        sunR.addColorStop(0.70, 'rgba(255, 210,  80, 0.15)');
        sunR.addColorStop(1,    'rgba(255, 210,  80, 0)');
        ctx.fillStyle = sunR;
        ctx.beginPath();
        ctx.arc(sunX, sunY, 52, 0, Math.PI * 2);
        ctx.fill();

        /* ── Sun light rays ── */
        for (var s = 0; s < 9; s++) {
            var spread = (s - 4) * 0.038;
            var rx0    = sunX + (s - 4) * 9;
            var rx1    = sunX + Math.sin(spread) * H * 1.4;
            var rayG   = ctx.createLinearGradient(sunX, sunY, rx1, H);
            rayG.addColorStop(0, 'rgba(255, 248, 200, 0.058)');
            rayG.addColorStop(1, 'rgba(255, 248, 200, 0)');
            ctx.beginPath();
            ctx.moveTo(rx0,      sunY);
            ctx.lineTo(rx1 + 16, H);
            ctx.lineTo(rx1 - 16, H);
            ctx.closePath();
            ctx.fillStyle = rayG;
            ctx.fill();
        }

        /* ── Ocean gradient (skyH → bottom) ── */
        var oceanGrad = ctx.createLinearGradient(0, skyH, 0, H);
        oceanGrad.addColorStop(0,    '#1EB4E4');   // bright surface turquoise
        oceanGrad.addColorStop(0.12, '#1592C6');   // shallow
        oceanGrad.addColorStop(0.35, '#0D72A6');   // mid-ocean
        oceanGrad.addColorStop(0.65, '#084E7E');   // deep
        oceanGrad.addColorStop(1,    '#03223E');   // abyssal
        ctx.fillStyle = oceanGrad;
        ctx.fillRect(0, skyH, W, H - skyH);

        /* ── Sunlight shimmer path (diagonal band across water) ── */
        var shimGrad = ctx.createLinearGradient(W * 0.4, skyH, W * SUN_X, H);
        shimGrad.addColorStop(0,   'rgba(255, 248, 170, 0.22)');
        shimGrad.addColorStop(0.5, 'rgba(255, 242, 130, 0.12)');
        shimGrad.addColorStop(1,   'rgba(255, 232, 100, 0)');
        ctx.beginPath();
        ctx.moveTo(W * (SUN_X - 0.12), skyH);
        ctx.lineTo(W * (SUN_X + 0.12), skyH);
        ctx.lineTo(W,                   H);
        ctx.lineTo(W * 0.25,            H);
        ctx.closePath();
        ctx.fillStyle = shimGrad;
        ctx.fill();

        /* ── Animated wave fill layers ── */
        var t = frame * 0.016;
        for (var li = 0; li < WAVES.length; li++) {
            var wv = WAVES[li];
            ctx.beginPath();
            for (var x = 0; x <= W + 3; x += 3) {
                var y = waveY(x, wv, t);
                if (x === 0) { ctx.moveTo(0, H); ctx.lineTo(0, y); }
                else         { ctx.lineTo(x, y); }
            }
            ctx.lineTo(W, H);
            ctx.closePath();
            ctx.fillStyle = wv.col;
            ctx.fill();
        }

        /* ── Wave-crest highlight lines ── */
        for (var ci = 0; ci < WAVES.length - 1; ci++) {
            ctx.beginPath();
            for (var x = 0; x <= W + 2; x += 2) {
                var y = waveY(x, WAVES[ci], t);
                if (x === 0) ctx.moveTo(0, y); else ctx.lineTo(x, y);
            }
            ctx.strokeStyle = 'rgba(190, 240, 255, 0.14)';
            ctx.lineWidth = 1.4;
            ctx.stroke();
        }

        /* ── Foam / sea-spray particles ── */
        for (var fi = 0; fi < foam.length; fi++) {
            var f   = foam[fi];
            f.x    += f.dx;
            f.life += 0.005;
            var fwy = waveY(f.x, WAVES[f.wi], t);
            if (f.life > 1 || f.x > W + 8) {
                foam[fi]   = newFoam();
                foam[fi].x = Math.random() * W * 0.4;
            } else {
                ctx.beginPath();
                ctx.arc(f.x, fwy - 1 + (Math.random() - 0.5) * 3, f.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(225, 248, 255,' + (f.o * (1 - f.life * 0.55)).toFixed(3) + ')';
                ctx.fill();
            }
        }

        /* ── Sun-glint sparkles ── */
        for (var si = 0; si < sparkles.length; si++) {
            var sp = sparkles[si];
            sp.life += 0.016;
            if (sp.life > sp.maxLife) { sparkles[si] = newSparkle(); continue; }
            var prog = sp.life / sp.maxLife;
            var ao   = Math.sin(prog * Math.PI) * 0.82;
            ctx.beginPath();
            ctx.arc(sp.x, sp.y, sp.sz, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 252, 200,' + ao.toFixed(3) + ')';
            ctx.fill();
            /* cross-flare arms */
            var arm = sp.sz * 3.2;
            ctx.beginPath();
            ctx.moveTo(sp.x - arm, sp.y); ctx.lineTo(sp.x + arm, sp.y);
            ctx.moveTo(sp.x, sp.y - arm); ctx.lineTo(sp.x, sp.y + arm);
            ctx.strokeStyle = 'rgba(255, 250, 215,' + (ao * 0.40).toFixed(3) + ')';
            ctx.lineWidth = 0.7;
            ctx.stroke();
        }

        /* ── Cursor water-ripple rings ── */
        for (var ri = ripples.length - 1; ri >= 0; ri--) {
            var rip = ripples[ri];

            /* outer ring */
            ctx.beginPath();
            ctx.arc(rip.x, rip.y, rip.r, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(150, 228, 255,' + rip.o.toFixed(3) + ')';
            ctx.lineWidth = 1.8;
            ctx.stroke();

            /* mid ring */
            if (rip.r > 12) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.60, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(200, 242, 255,' + (rip.o * 0.52).toFixed(3) + ')';
                ctx.lineWidth = 1.1;
                ctx.stroke();
            }

            /* inner ring */
            if (rip.r > 26) {
                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.r * 0.30, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(230, 250, 255,' + (rip.o * 0.28).toFixed(3) + ')';
                ctx.lineWidth = 0.6;
                ctx.stroke();
            }

            rip.r += rip.spd;
            rip.o -= 0.010;
            if (rip.o <= 0 || rip.r > 115) ripples.splice(ri, 1);
        }

        frame++;
        requestAnimationFrame(draw);
    }

    draw();
}());
