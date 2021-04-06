

    <section id="bacs" class="content">

        <h2>Find mandate/make BACS change request</h2>

        <form id="search-mandates" class="search" action="" method="get">
          <input type="hidden" name="l" value="20" />
          <input type="hidden" name="t" value="m" />
          <input id="search" type="text" name="s" placeholder="Search for a mandate" />
          <input id="expert" type="checkbox" name="e" />Expert [ <a target="_blank" class="help" href="https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode">?</a> ]
        </form>

        <table>
          <tbody id="search-results">
          </tbody>
          <tfoot>
            <tr id="search-notice">
            </tr>
          </tfoot>
        </table>

        <form id="change-mandate">
          <input type="hidden" name="ClientRef" />
          <button id="post-mandate">Update</button>
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
window.top.menuActivate ('bacs');
window.updateHandle ('change-mandate');
    </script>



