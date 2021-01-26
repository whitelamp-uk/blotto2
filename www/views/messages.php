

<?php if(count($msg)): ?>

<?php   foreach($msg as $m): ?>
      <h4 class="splash ok"><?php echo htmlspecialchars($m); ?></h3>
<?php   endforeach; ?>

<?php endif; ?>



<?php if($err): ?>
      <h4 class="splash err"><?php echo htmlspecialchars($err); ?></h3>
<?php endif; ?>


      <script>
window.addEventListener ('beforeunload',unloading);
      </script>

