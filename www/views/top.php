

    <section id="options">

      <a id="summary" target="frame" href="./?summary" class="active">Summary</a>
      <a id="reconcile" target="frame" href="./?reconcile">Reconcile</a>
      <a id="Wins" target="frame" href="./?list=Wins">Wins</a>
      <a id="Draws" target="frame" href="./?list=Draws">Draws</a>
<?php if (!defined('BLOTTO_RBE_ORGS')): ?>
      <a id="Supporters" target="frame" href="./?list=Supporters">Supporters</a>
      <a id="Cancellations" target="frame" href="./?list=Cancellations">Cancellations</a>
      <a id="ANLs" target="frame" href="./?list=ANLs">ANLs</a>
      <a id="Insurance" target="frame" href="./?list=Insurance">Insure</a>
      <a id="Changes" target="frame" href="./?list=Changes">CCCs</a>
      <a id="Updates" target="frame" href="./?list=Updates">CRM</a>
<?php endif; ?>
      <a id="logout" href="./?logout">Log out</a>

    </section>


    <iframe id="frame" name="frame" src="" allowfullscreen>
    </iframe>

    <section id="footer">

      <a id="about" target="frame" href="./?about">About</a>
      <a id="terms" target="frame" href="./?terms">Terms</a>
      <a id="privacy" target="frame" href="./?privacy">Privacy</a>
      <a id="guide" target="frame" href="./?guide">Guide</a>
      <a id="support" target="frame" href="./?support">Support request</a>
      <a id="adminer" target="frame" href="./adminer.php">Go mining</a>
<?php if (!defined('BLOTTO_RBE_ORGS')): ?>
      <a id="bacs" target="frame" href="./?bacs">BACS request</a>
      <a id="supporter" target="frame" href="./?supporter">Supporter update</a>
<?php endif; ?>

    </section>


    <img id="logo" src="./logo-org.png"/>
    <img id="logo-blotto" src="./media/logo.png"/>


