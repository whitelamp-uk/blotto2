
    <section id="invoices" class="content">

        <h2>List of invoices</h2>

        <?php
        if (defined('BLOTTO_DIR_INVOICE')) {
          $files = scandir(BLOTTO_DIR_INVOICE, SCANDIR_SORT_DESCENDING);
          if (count($files>2)) {
            foreach ($files as $fn) {
              if ($fn != '.' && $fn != '..') {
                echo '<a href="?invoice='.$fn.'" download="'.$fn.'">'.$fn.'</a><br>';
              }
            }
          } else {
            echo "No invoices found!";
          }
        } else {
          echo "Invoice directory not configured";
        }
        ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('invoices');
//window.updateHandle ('change-mandate');
    </script>



