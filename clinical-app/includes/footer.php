<?php

/**
 * Promedico Clinical App Footer
 * Path: public/clinical/includes/footer.php
 */

declare(strict_types=1);

$currentYear = date('Y');
?>
</main>

<footer class="clinical-footer">
    <div class="clinical-footer__inner">
        <span>
            &copy; <?= $currentYear ?> Promedico. Clinical records area.
        </span>

        <span>
            Staff-only system. Do not leave patient records visible on unattended devices.
        </span>
    </div>
</footer>
</body>

</html>