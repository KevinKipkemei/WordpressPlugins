document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.kk-sp-form');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const container = form.closest('.kk-sp-container');
            const pollId = container.getAttribute('data-poll-id');
            const messageDiv = container.querySelector('.kk-sp-message');
            const selectedOption = form.querySelector('input[name="kk_sp_option_index"]:checked');

            if (!selectedOption) {
                showMessage(messageDiv, 'Please select an option.', 'error');
                return;
            }

            const optionIndex = selectedOption.value;
            const submitBtn = form.querySelector('.kk-sp-submit-btn');
            
            // Disable button during submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Voting...';

            const formData = new FormData();
            formData.append('action', 'kk_sp_submit_vote');
            formData.append('nonce', kkSpData.nonce);
            formData.append('poll_id', pollId);
            formData.append('option_index', optionIndex);

            fetch(kkSpData.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(messageDiv, data.data.message, 'success');
                    renderResults(container, data.data);
                } else {
                    showMessage(messageDiv, data.data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Vote';
                }
            })
            .catch(error => {
                showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Vote';
            });
        });
    });

    function showMessage(element, text, type) {
        element.textContent = text;
        element.className = 'kk-sp-message ' + type;
        element.style.display = 'block';
    }

    function renderResults(container, data) {
        const form = container.querySelector('.kk-sp-form');
        const resultsContainer = container.querySelector('.kk-sp-results-container');
        
        // Hide form and show results container
        form.style.display = 'none';
        resultsContainer.style.display = 'block';
        resultsContainer.innerHTML = ''; // clear any existing

        const style = data.style; // 'bar' or 'percentage'
        const totalVotes = data.total_votes;

        data.results.forEach(function(result) {
            const pct = totalVotes > 0 ? Math.round((result.total / totalVotes) * 100) : 0;
            
            const row = document.createElement('div');
            row.className = 'kk-sp-result-row';

            const label = document.createElement('div');
            label.className = 'kk-sp-result-label';
            label.textContent = result.text + ' (' + result.total + ' votes)';
            row.appendChild(label);

            if (style === 'bar') {
                const barBg = document.createElement('div');
                barBg.className = 'kk-sp-bar-bg';
                
                const bar = document.createElement('div');
                bar.className = 'kk-sp-bar';
                // Start with 0 width, animate to pct
                bar.style.width = '0%';

                const pctText = document.createElement('span');
                pctText.className = 'kk-sp-pct';
                pctText.textContent = pct + '%';

                bar.appendChild(pctText);
                barBg.appendChild(bar);
                row.appendChild(barBg);

                // Trigger animation after brief delay
                setTimeout(function() {
                    bar.style.width = pct + '%';
                }, 100);
            } else {
                // Percentage text only
                const textOnly = document.createElement('div');
                textOnly.textContent = pct + '% of total votes';
                row.appendChild(textOnly);
            }

            resultsContainer.appendChild(row);
        });
    }
});
