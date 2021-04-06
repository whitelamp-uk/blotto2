

    <section id="supporter" class="content">

        <h2>Find/update supporter details</h2>

        <form id="search-supporters" class="search" action="" method="get">
          <input type="hidden" name="l" value="20" />
          <input type="hidden" name="t" value="s" />
          <input id="search" type="text" name="s" placeholder="Search for a supporter" />
          <input id="expert" type="checkbox" name="e" />Expert [ <a title="Open reference doc in new tab" target="_blank" class="help" href="https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode">?</a> ]
        </form>

        <table>
          <tbody id="search-results">
          </tbody>
          <tfoot>
            <tr id="search-notice">
            </tr>
          </tfoot>
        </table>

        <form id="change-supporter">
          <input type="hidden" name="supporter_id" />
          <button id="post-supporter" name="update">Update</button>
          <button class="form-close">Cancel</button>
          <table>
            <thead>
              <tr>
                <th>&nbsp;</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </form>

    </section>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('supporter');
window.updateHandle ('change-supporter');
    </script>



