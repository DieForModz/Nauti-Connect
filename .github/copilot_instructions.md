# Copilot Development Instructions - STRICT COMPLIANCE REQUIRED

## ⚠️ CRITICAL TECH STACK CONSTRAINTS

**UNDER NO CIRCUMSTANCES** may you deviate from the following approved technologies:

### ✅ AUTHORIZED TECHNOLOGIES ONLY
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP (7.4+ or 8.x)
- **Database**: MySQL/MariaDB (if needed, via PHP PDO only)
- **Server**: Apache/Nginx with PHP-FPM

### ❌ STRICTLY PROHIBITED
- NO Frameworks (React, Vue, Angular, Svelte, etc.)
- NO Build Tools (Webpack, Vite, Parcel, etc.)
- NO Package Managers (npm, yarn, pnpm for frontend)
- NO CSS Preprocessors (Sass, Less, Stylus)
- NO TypeScript
- NO Node.js for frontend build steps
- NO Python, Ruby, Go, or other backend languages
- NO External CDNs unless explicitly requested
- NO jQuery (use vanilla JS only)
- NO Bootstrap, Tailwind, or CSS frameworks (write custom CSS3)

---

## 🎯 DEVELOPMENT PHASES & MANDATORY DEBUG CHECKPOINTS

Every task MUST follow this exact workflow. You **CANNOT** proceed to the next phase until the current phase passes all debug checks.

### PHASE 1: Requirements & Architecture
**Action**: Analyze requirements and plan file structure

**Debug Checkpoints**:
- [ ] List all files to be created/modified
- [ ] Confirm zero external dependencies
- [ ] Verify tech stack compliance
- [ ] Validate PHP version compatibility

**Output**: File structure tree + tech stack confirmation

---

### PHASE 2: HTML5 Structure
**Action**: Create semantic, accessible HTML5 markup

**Debug Checkpoints**:
- [ ] Validate HTML5 using W3C standards
- [ ] Check semantic tag usage (header, nav, main, section, article, footer)
- [ ] Verify accessibility attributes (aria-labels, alt text, roles)
- [ ] Test responsive viewport meta tag
- [ ] Confirm no inline styles (all styling via external CSS)

**Output**: Complete `.html` file(s) with comments marking sections

---

### PHASE 3: CSS3 Styling
**Action**: Write modern, responsive CSS3

**Debug Checkpoints**:
- [ ] Validate CSS3 using W3C standards
- [ ] Check Flexbox/Grid usage for layouts (no float-based layouts)
- [ ] Verify media queries for responsive breakpoints
- [ ] Test CSS variables for theming consistency
- [ ] Confirm vendor prefixes for compatibility (-webkit-, -moz-)
- [ ] Validate color contrast ratios (WCAG 2.1 AA minimum)
- [ ] Check animation performance (use transform/opacity only)

**Output**: Complete `.css` file(s) with organized sections

---

### PHASE 4: JavaScript Functionality
**Action**: Implement vanilla JavaScript (ES6+)

**Debug Checkpoints**:
- [ ] Validate ES6+ syntax without transpilation
- [ ] Check for memory leaks (event listener cleanup)
- [ ] Verify DOM manipulation efficiency
- [ ] Test error handling (try-catch blocks)
- [ ] Confirm no console errors in browser dev tools
- [ ] Validate form validation logic
- [ ] Check AJAX/Fetch API error handling
- [ ] Test cross-browser compatibility (Chrome, Firefox, Safari, Edge)

**Output**: Complete `.js` file(s) with JSDoc comments

---

### PHASE 5: PHP Backend
**Action**: Create secure, efficient PHP backend

**Debug Checkpoints**:
- [ ] Validate PHP syntax (lint check)
- [ ] Check for SQL injection vulnerabilities (use PDO prepared statements ONLY)
- [ ] Verify XSS protection (htmlspecialchars output encoding)
- [ ] Test CSRF token implementation for forms
- [ ] Validate input sanitization and validation
- [ ] Check error logging (no exposed errors in production)
- [ ] Verify session security (regenerate IDs, secure flags)
- [ ] Test file upload security (if applicable)
- [ ] Validate JSON response formatting for AJAX endpoints
- [ ] Check HTTP status codes (200, 400, 401, 403, 404, 500)

**Output**: Complete `.php` file(s) with security audit notes

---

### PHASE 6: Integration Testing
**Action**: Connect frontend and backend

**Debug Checkpoints**:
- [ ] Test AJAX/Fetch requests from JS to PHP
- [ ] Verify CORS headers if cross-origin (though same-origin preferred)
- [ ] Check JSON parsing errors
- [ ] Validate form submissions (GET/POST/PUT/DELETE)
- [ ] Test file upload/download workflows
- [ ] Verify session persistence across requests
- [ ] Check database connection pooling (if applicable)
- [ ] Test error propagation (JS catches PHP errors gracefully)

**Output**: Integration test results + network tab screenshots

---

### PHASE 7: Security Audit
**Action**: Final security review

**Debug Checkpoints**:
- [ ] Run OWASP ZAP or manual security checklist
- [ ] Verify no exposed credentials in code
- [ ] Check .htaccess rules (deny access to sensitive files)
- [ ] Validate HTTPS enforcement (if production)
- [ ] Test rate limiting on endpoints
- [ ] Verify input validation on all entry points
- [ ] Check for information disclosure (PHP version hiding)

**Output**: Security audit report

---

### PHASE 8: Performance Optimization
**Action**: Optimize for speed and efficiency

**Debug Checkpoints**:
- [ ] Minify CSS/JS (manual or simple PHP script, no build tools)
- [ ] Optimize images (WebP format with fallbacks)
- [ ] Check lazy loading implementation
- [ ] Verify database query optimization (indexes, limits)
- [ ] Test Gzip/Brotli compression (server-level)
- [ ] Validate caching headers
- [ ] Check Core Web Vitals (LCP < 2.5s, FID < 100ms, CLS < 0.1)

**Output**: Performance metrics + Lighthouse score

---

### PHASE 9: Final Validation
**Action**: Complete system test

**Debug Checkpoints**:
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsiveness (iOS Safari, Android Chrome)
- [ ] Accessibility audit (axe-core or WAVE)
- [ ] Functionality test (all user flows)
- [ ] Error scenario testing (404, 500, network failure)
- [ ] Code review (PSR-12 standards for PHP, consistent JS style)

**Output**: Final validation report + deployment checklist

---

## 🔒 COMPLIANCE ENFORCEMENT

### If Asked to Use Prohibited Tech:
**RESPONSE**: "I cannot use [TECHNOLOGY] as it violates the strict tech stack policy (HTML5, CSS3, JS, PHP only). I will implement this using [ALTERNATIVE APPROVED TECH]."

### If Code Requires Debugging:
**MANDATORY**: Stop and fix before proceeding. Do not generate more code until current phase passes all checkpoints.

### File Organization:
project-root/ ├── index.html # Main entry point ├── css/ │ ├── reset.css # CSS reset/normalize │ ├── variables.css # CSS custom properties │ ├── layout.css # Grid/Flexbox layouts │ ├── components.css # Reusable components │ └── main.css # Main stylesheet ├── js/ │ ├── utils.js # Utility functions │ ├── api.js # PHP API calls │ ├── components.js # Component logic │ └── main.js # Entry point ├── php/ │ ├── config.php # Database/config │ ├── auth.php # Authentication │ ├── api/ # API endpoints │ └── includes/ # Shared PHP functions └── .htaccess # Security rules

---

## 📝 CODE STANDARDS

### HTML5 Requirements:
- Semantic markup only
- `data-*` attributes for JS hooks (no IDs for styling)
- Lazy loading for images: `loading="lazy"`
- Picture element for responsive images

### CSS3 Requirements:
- Mobile-first media queries
- CSS Grid for 2D layouts, Flexbox for 1D
- CSS variables for colors, spacing, typography
- `rem` units for accessibility
- `box-sizing: border-box` universal

### JavaScript Requirements:
- Strict mode: `'use strict';`
- ES6 modules if multiple files (type="module")
- Async/await for Promises
- Event delegation for dynamic elements
- No global variables (use IIFE or modules)

### PHP Requirements:
- PSR-12 coding standards
- PDO for all database operations
- Prepared statements mandatory
- Output buffering for clean JSON responses
- Error logging to file, never display

---

## 🚨 REMINDER

**Before every response, verify:**
1. Am I using ONLY HTML5, CSS3, JS, PHP?
2. Did I complete all debug checkpoints for the current phase?
3. Is the code secure against OWASP Top 10?
4. Is the code accessible (WCAG 2.1 AA)?

**Non-compliance is not acceptable.**
