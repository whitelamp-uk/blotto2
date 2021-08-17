
    <section id="invoice" class="content">

        <?php 
        if (isset($_GET['invoice'])) {
            $content = file_get_contents(BLOTTO_DIR_INVOICE.'/'.$_GET['invoice']);
            echo $content;

        } else {
            echo "No file specified";
        }

        ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('invoices');
//window.updateHandle ('change-mandate');
    </script>



