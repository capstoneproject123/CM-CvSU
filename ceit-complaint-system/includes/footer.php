</main>
</div>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col footer-brand">
            <div class="footer-title">CEIT · CvSU</div>
            <p class="footer-text">College of Engineering and Information Technology<br>Cavite State University, Indang, Cavite</p>
        </div>
        <div class="footer-col">
            <div class="footer-heading">Vision</div>
            <p class="footer-text">The premier university in historic Cavite globally recognized for excellence in character development, academics, research, innovation and sustainable community engagement.</p>
        </div>
        <div class="footer-col">
            <div class="footer-heading">Mission</div>
            <p class="footer-text">Cavite State University shall provide excellent, equitable and relevant educational opportunities in the arts, sciences and technology through quality instruction and responsive research and development activities. It shall produce professional, skilled and morally upright individuals for global competitiveness.</p>
        </div>
        <div class="footer-col">
            <div class="footer-heading">Core Values</div>
            <p class="footer-text">Truth · Integrity · Excellence · Service</p>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> Cavite State University</span>
        <a href="https://cvsu.edu.ph/mission-vision-objectives/" target="_blank" rel="noopener">Mission, Vision, Objectives – Full Statement ↗</a>
    </div>
</footer>
<script>window.APP_BASE = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=<?= @filemtime(__DIR__ . '/../assets/js/script.js') ?: time() ?>"></script>
</body>
</html>

