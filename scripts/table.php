
<table id="<?php echo htmlspecialchars($id); ?>" class="<?php echo htmlspecialchars($class); ?>">
<?php if($caption): ?>
  <caption><?php echo htmlspecialchars($caption); ?></caption>
<?php endif; ?>
<?php if($headings): ?>
  <thead>
    <tr>
<?php     foreach ($headings as $heading): ?>
      <th><?php echo htmlspecialchars($heading); ?></th>
<?php     endforeach; ?>
    </tr>
  </thead>
<?php endif; ?>
<?php if($data): ?>
  <tbody>
<?php     foreach ($data as $i=>$row): ?>
    <tr<?php if (is_array($classes) && array_key_exists($i,$classes)): ?> class="<?php echo htmlspecialchars($classes[$i]); ?>"<?php endif; ?>>
<?php         foreach ($row as $cell): ?>
      <td><?php echo htmlspecialchars($cell); ?></td>
<?php         endforeach; ?>
    </tr>
<?php     endforeach; ?>
  </tbody>
<?php endif; ?>
<?php if($headings && !count($data)): ?>
  <tfoot>
    <tr>
      <td colspan="<?php echo count($headings); ?>">No results</td>
    </tr>
  </tfoot>
<?php elseif($footings): ?>
  <tfoot>
<?php     foreach ($footings as $row): ?>
    <tr>
<?php         foreach ($row as $cell): ?>
      <td><?php echo htmlspecialchars($cell); ?></td>
<?php         endforeach; ?>
    </tr>
<?php     endforeach; ?>
  </tfoot>
<?php endif; ?>
</table>

