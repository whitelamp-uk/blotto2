
    <section id="invoices" class="content">

        <h2>List of invoices</h2>

        <?php
        if (defined(BLOTTO_INVOICE_DIR)) {
          $files = scandir(BLOTTO_INVOICE_DIR, SCANDIR_SORT_DESCENDING);
          if (count($files>2)) {
            foreach ($files as $fn) {
              if ($fn != '.' && $fn != '..') {
                echo '<a target="_blank" href="?invoice='.$fn.'">'.$fn.'</a><br>';
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



