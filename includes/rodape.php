    </main>
    <footer class="rodape-app">
      <?= e($config['app_nome']) ?> · Projeto de portfólio de
      <a href="https://marianaramosti.github.io/portfolio" target="_blank" rel="noopener">Mariana Ramos</a>
      · PHP, MySQL e JavaScript
    </footer>
  </div>

  <div class="sombra-menu" id="sombraMenu"></div>
  <?php if (!empty($usarGraficos)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php endif; ?>
  <script src="assets/js/app.js"></script>
</body>
</html>
