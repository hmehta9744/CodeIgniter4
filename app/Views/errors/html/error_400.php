<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title><?= lang('Errors.badRequest') ?></title>
    <link href='<?php echo base_url(); ?>css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='d-flex align-items-center justify-content-center vh-100'>
        <div class='text-center'>
            <h1 class='display-1 fw-bold'>400</h1>
            <p class='lead'>
                <?php if (ENVIRONMENT !== 'production') : ?>
                    <?= nl2br(esc($message)) ?><br>
                <?php endif; ?>
                <?= lang('Errors.sorryBadRequest', [], 'es') ?>

            </p>
            <a href='<?php echo base_url(); ?>' class='btn btn-primary'>Go Home</a>
        </div>
    </div>
    <footer>
        <em>&copy; <?php echo date('Y') . ' La Barrigona'; ?></em>
    </footer>
    <script src='<?php echo base_url(); ?>js/bootstrap.bundle.min.js'></script>
</body>
</html>
