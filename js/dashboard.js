(function () {
    function iniciarFavoritos() {
        document.querySelectorAll('.estrela').forEach(function (estrela, indice) {
            var chave = 'trainhub-area-favorita-' + indice;
            estrela.setAttribute('role', 'button');
            estrela.setAttribute('tabindex', '0');
            estrela.setAttribute('aria-label', 'Marcar área como favorita');
            var atualizar = function (favorita) {
                estrela.textContent = favorita ? '★' : '☆';
                estrela.classList.toggle('favorito', favorita);
                estrela.setAttribute('aria-pressed', favorita ? 'true' : 'false');
            };
            atualizar(window.localStorage.getItem(chave) === '1');
            estrela.addEventListener('click', function (evento) {
                evento.preventDefault();
                evento.stopPropagation();
                var favorita = estrela.getAttribute('aria-pressed') !== 'true';
                window.localStorage.setItem(chave, favorita ? '1' : '0');
                atualizar(favorita);
            });
            estrela.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    estrela.click();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarFavoritos);
    } else {
        iniciarFavoritos();
    }
}());
