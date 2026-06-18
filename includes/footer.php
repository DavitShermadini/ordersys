</div><!-- /.container -->

<footer class="mt-5 py-4 bg-dark text-white">
    <div class="container text-center">
        <p class="mb-0 text-muted">&copy; <?= date('Y') ?> OrderSys &mdash; B2B შეკვეთების მართვის სისტემა</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$_html = ob_get_clean();
if (APP_BASE !== '') {
    // Rewrite all absolute-path HTML attributes to include the subdirectory prefix
    $_html = preg_replace(
        '/((?:href|action|src)=")(\/)/',
        '$1' . APP_BASE . '/',
        $_html
    );
}
echo $_html;
?>
