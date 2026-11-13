<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="/assets/images/logo_itamambuca.png" alt="Itamambuca Eco Resort" onerror="this.src='/assets/logo.png'">
        <p>Experimente a natureza e o conforto na praia de Itamambuca. Acumule pontos e troque por recompensas exclusivas em nosso Resort.</p>
      </div>
      <div class="footer-menus">
        <div>
          <h4>Navegação</h4>
          <ul>
            <li><a href="/">Início</a></li>
            <li><a href="/busca.php">Explorar Atrações</a></li>
            <li><a href="/ranking.php">Ranking de Hóspedes</a></li>
            <li><a href="/favoritos.php">Favoritos</a></li>
          </ul>
        </div>
        <div>
          <h4>Resort</h4>
          <ul>
            <li><a href="https://itamambuca.com.br" target="_blank">Site Principal</a></li>
            <li><a href="https://itamambuca.com.br/o-resort/" target="_blank">O Resort</a></li>
            <li><a href="https://itamambuca.com.br/contato/" target="_blank">Contato</a></li>
            <li><a href="https://wa.me/551238343000" target="_blank">WhatsApp</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Itamambuca Eco Resort. Todos os direitos reservados.</span>
      <a href="https://itamambuca.com.br">itamambuca.com.br</a>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scripts -->
<script>
// Mobile menu toggle (se necessário no futuro)
document.addEventListener('DOMContentLoaded', function() {
  // Favorite buttons
  document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const partnerId = this.dataset.partnerId;
      fetch('/api/toggle-favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ partner_id: partnerId })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          this.classList.toggle('active');
          const icon = this.querySelector('i');
          if (icon) {
            icon.className = this.classList.contains('active') ? 'bi bi-heart-fill' : 'bi bi-heart';
          }
        }
      })
      .catch(() => {});
    });
  });
});
</script>
</body>
</html>
