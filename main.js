// Notification bell count
function updateNotifCount() {
    fetch('/DSTLib/getunreadcount.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notifCount');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Notification error:', err));
}

/* This function is to ensure that the search bar automatically brings up results as one types.  */
function initSearch() {
    const input = document.getElementById('searchInput');
    const results = document.getElementById('searchResults');
    if (!input || !results) return; 

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();

        if (q.length < 2) {
            results.style.display = 'none';
            results.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch('/DSTLib/search.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';

                    if (data.length === 0) {
                        results.innerHTML = '<div class="search-no-results">No results found.</div>';
                        results.style.display = 'block';
                        return;
                    }

                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.href = item.url;
                        a.className = 'search-result-item';

                        const img = document.createElement('img');
                        if (item.type === 'book') img.src = item.cover;
                        else if (item.type === 'user') img.src = item.avatar;
                        img.onerror = () => img.style.display = 'none';

                        const text = document.createElement('div');
                        text.className = 'search-result-label';
                        text.textContent = item.label;

                        const type = document.createElement('div');
                        type.className = 'search-result-type';
                        type.textContent = item.type;

                        a.appendChild(img);
                        a.appendChild(text);
                        a.appendChild(type);
                        results.appendChild(a);
                    });

                    results.style.display = 'block';
                })
                .catch(err => console.error('Search error:', err));
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-wrapper')) {
            results.style.display = 'none';
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    updateNotifCount();
    setInterval(updateNotifCount, 30000);
    initSearch();
});

function toggleReportForm(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}