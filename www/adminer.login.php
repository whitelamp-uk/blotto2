
<style>

<?php if(BLOTTO_ADMINER_PW): ?>
#blotto-login {
    visibility: hidden;
}
<?php endif; ?>

#blotto-login + p {
    visibility: hidden;
}

</style>

<table id="blotto-login">
  <tr>
    <th>
      System
    </th>
    <td>
      <select name="auth[driver]">
        <option value="server" selected="selected">MySQL</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>
      Server
    </th>
    <td>
      <input name="auth[server]" value="" title="hostname[:port]" placeholder="localhost" autocapitalize="off" />
    </td>
  </tr>
  <tr>
    <th>
      Username
    </th>
    <td>
      <input name="auth[username]" id="username" value="<?php echo htmlspecialchars(BLOTTO_ADMINER_UN); ?>" autocomplete="username" autocapitalize="off" />
      <script nonce="<?php echo get_nonce(); ?>">
focus(qs('#username')); qs('#username').form['auth[driver]'].onchange();
      </script>
    </td>
  </tr>
  <tr>
    <th>
      Password
    </th>
    <td>
      <input type="password" name="auth[password]" value="<?php echo htmlspecialchars(BLOTTO_ADMINER_PW); ?>" autocomplete="current-password" />
    </td>
  </tr>
  <tr>
    <th>
      Database
    </th>
    <td>
      <input name="auth[db]" value="<?php echo htmlspecialchars(BLOTTO_ADMINER_DB); ?>" autocapitalize="off" />
    </td>
  </tr>
</table>

<p>
  <input type="submit" id="blotto-login-button" value="Login" />
  <label>
    <input type='checkbox' name='auth[permanent]' value='1'>
    Permanent login
  </label>
</p>

<?php if(BLOTTO_ADMINER_PW): ?>
  <script nonce="<?php echo get_nonce(); ?>">
document.getElementById ('blotto-login-button') .click ();
  </script>
<?php endif; ?>




