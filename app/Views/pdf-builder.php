<?php include __DIR__.'/../includes/header.php'; ?>

<div class="container">
    <h1>Gerador de PDF</h1>
    
    <form id="pdfForm">
        <div class="form-group">
            <label>Nome do Arquivo:</label>
            <input type="text" name="filename" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Conteúdo:</label>
            <textarea name="content" class="form-control" rows="10" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <span class="submit-text">Gerar PDF</span>
            <span class="spinner-border spinner-border-sm d-none"></span>
        </button>
    </form>
    
    <div id="result" class="mt-3"></div>
</div>

<script>
document.getElementById('pdfForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    const submitText = submitBtn.querySelector('.submit-text');
    
    submitText.textContent = 'Gerando...';
    spinner.classList.remove('d-none');
    submitBtn.disabled = true;

    try {
        const response = await fetch('/generate-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                filename: form.filename.value,
                content: form.content.value
            })
        });

        const data = await response.json();
        
        if (data.success) {
            const downloadLink = document.createElement('a');
            downloadLink.href = data.pdf_url;
            downloadLink.target = '_blank';
            downloadLink.className = 'btn btn-success';
            downloadLink.textContent = 'Download PDF';
            
            document.getElementById('result').innerHTML = '';
            document.getElementById('result').appendChild(downloadLink);
        } else {
            showError(data.error || 'Erro ao gerar PDF');
        }
    } catch (error) {
        showError('Falha na comunicação com o servidor');
    } finally {
        submitText.textContent = 'Gerar PDF';
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
    }
});

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger';
    alert.textContent = message;
    
    document.getElementById('result').innerHTML = '';
    document.getElementById('result').appendChild(alert);
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>