document.addEventListener('DOMContentLoaded', function () {
    // Exemplo para o formulário do PDF Builder
    const pdfForm = document.getElementById('pdfForm');

    if (pdfForm) {
        pdfForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Impede o envio tradicional do formulário

            // Coleta os dados do formulário
            const formData = new FormData(pdfForm);

            // Adiciona dados extras se necessário
            formData.append('action', 'generate_pdf');

            // Mostra um loader (opcional)
            const submitBtn = pdfForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Gerando... <span class="spinner"></span>';
            submitBtn.disabled = true;

            // Faz a requisição AJAX
            fetch('<?= BASE_URL ?>/pdf-builder/generate', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na requisição');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Sucesso - mostra mensagem ou faz download do PDF
                        alert('PDF gerado com sucesso!');
                        if (data.pdf_url) {
                            window.open(data.pdf_url, '_blank');
                        }
                    } else {
                        // Erro do servidor
                        alert('Erro: ' + (data.message || 'Erro ao gerar PDF'));
                    }
                })
                .catch(error => {
                    // Erro de rede ou na requisição
                    console.error('Erro:', error);
                    alert('Falha na comunicação com o servidor');
                })
                .finally(() => {
                    // Restaura o botão
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
        });
    }
});