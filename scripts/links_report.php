
          <nav class="links">
            <a
              title="Download data as CSV"
              class="link-resource link-csv"
              download="<?php echo htmlspecialchars ($fname); ?>.csv"
              href="./?report&amp;type=csv&amp;fn=<?php echo htmlspecialchars ($fname); ?>&amp;nr=<?php echo htmlspecialchars ($number); ?>&amp;xh=<?php echo htmlspecialchars ($xhead); ?><?php echo $params; ?>"
            ><img /></a>
            <a
              title="Download graph as PNG"
              class="link-resource link-image"
              download="<?php echo htmlspecialchars ($fname); ?>.png"
            ><img /></a>
            <a
              title="Download data as HTML"
              class="link-resource link-table"
              download="<?php echo htmlspecialchars ($fname); ?>.html"
              href="./?report&amp;type=table&amp;fn=<?php echo htmlspecialchars ($fname); ?>&amp;nr=<?php echo htmlspecialchars ($number); ?>&amp;xh=<?php echo htmlspecialchars ($xhead); ?><?php echo $params; ?>"
            ><img /></a>
          </nav>
