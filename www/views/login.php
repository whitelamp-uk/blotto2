

    <section id="login" class="<?php if (!is_https()): ?>in<?php endif; ?>secure">

      <form class="login" action="./" method="post">
        <div class="usr">
          <input type="text" name="un" placeholder="Username" />
        </div>
        <div class="pwd">
          <input type="password" name="pw" placeholder="Password" /><input type="submit" name="auth" value="Go" />
        </div>
      </form>

      <img id="logo-login-form" src="./media/logo-login-form.png"/>

    </section>

    <img id="logo-login" src="./media/logo-login.png"/>
    <img id="logo" src="./logo-org.png"/>





