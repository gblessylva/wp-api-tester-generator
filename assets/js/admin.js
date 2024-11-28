document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-endpoint-form');
    const endpointsList = document.getElementById('endpoints-list');
    const databaseSelect = document.getElementById('database');
    const columnsContainer = document.getElementById('columns-container');
    const columnsList = document.getElementById('columns-list');
    const testQueryBtn = document.getElementById('test-query');
    const responsePreview = document.getElementById('response-preview');
    const responseTextarea = document.getElementById('response');
    const queryTypeSelect = document.getElementById('query-type');
    const tableQueryContainer = document.getElementById('table-query-container');
    const customQueryContainer = document.getElementById('custom-query-container');
    const customQueryTextarea = document.getElementById('custom-query');

    // Load existing endpoints
    loadEndpoints();
    // Load database tables
    loadDatabaseTables();

    // Handle query type selection
    queryTypeSelect.addEventListener('change', function() {
        if (this.value === 'table') {
            tableQueryContainer.style.display = 'block';
            customQueryContainer.style.display = 'none';
        } else {
            tableQueryContainer.style.display = 'none';
            customQueryContainer.style.display = 'block';
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        createEndpoint();
    });

    // Handle database table selection
    databaseSelect.addEventListener('change', function() {
        if (this.value) {
            loadTableColumns(this.value);
        } else {
            columnsContainer.style.display = 'none';
        }
    });

    // Handle test query button
    testQueryBtn.addEventListener('click', function() {
        if (queryTypeSelect.value === 'table') {
            testTableQuery();
        } else {
            testCustomQuery();
        }
    });

    async function loadDatabaseTables() {
        try {
            const response = await fetch(wpApiTester.apiUrl + '/tables', {
                headers: {
                    'X-WP-Nonce': wpApiTester.nonce
                }
            });
            const tables = await response.json();
            
            databaseSelect.innerHTML = '<option value="">Select a table...</option>' +
                tables.map(table => `<option value="${escapeHtml(table)}">${escapeHtml(table)}</option>`).join('');
        } catch (error) {
            console.error('Error loading tables:', error);
        }
    }

    async function loadTableColumns(table) {
        try {
            const response = await fetch(wpApiTester.apiUrl + '/columns/' + encodeURIComponent(table), {
                headers: {
                    'X-WP-Nonce': wpApiTester.nonce
                }
            });
            const columns = await response.json();
            
            columnsList.innerHTML = columns.map(column => `
                <div class="column-checkbox">
                    <label>
                        <input type="checkbox" name="columns[]" value="${escapeHtml(column)}">
                        ${escapeHtml(column)}
                    </label>
                </div>
            `).join('');
            
            columnsContainer.style.display = 'block';
        } catch (error) {
            console.error('Error loading columns:', error);
        }
    }

    async function testTableQuery() {
        const table = databaseSelect.value;
        const selectedColumns = Array.from(document.querySelectorAll('input[name="columns[]"]:checked'))
            .map(checkbox => checkbox.value);

        if (!table || selectedColumns.length === 0) {
            alert('Please select a table and at least one column');
            return;
        }

        try {
            const response = await fetch(wpApiTester.apiUrl + '/test-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiTester.nonce
                },
                body: JSON.stringify({
                    queryType: 'table',
                    table,
                    columns: selectedColumns
                })
            });

            const results = await response.json();
            const prettyResults = JSON.stringify(results, null, 2);
            responsePreview.textContent = prettyResults;
            responseTextarea.value = prettyResults;
        } catch (error) {
            console.error('Error testing query:', error);
            alert('Error testing query');
        }
    }

    async function testCustomQuery() {
        const query = customQueryTextarea.value.trim();
        if (!query) {
            alert('Please enter a SQL query');
            return;
        }

        try {
            const response = await fetch(wpApiTester.apiUrl + '/test-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiTester.nonce
                },
                body: JSON.stringify({
                    queryType: 'custom',
                    query
                })
            });

            const results = await response.json();
            const prettyResults = JSON.stringify(results, null, 2);
            responsePreview.textContent = prettyResults;
            responseTextarea.value = prettyResults;
        } catch (error) {
            console.error('Error testing query:', error);
            alert('Error testing query');
        }
    }

    async function loadEndpoints() {
        try {
            const response = await fetch(wpApiTester.apiUrl + '/endpoints', {
                headers: {
                    'X-WP-Nonce': wpApiTester.nonce
                }
            });
            const data = await response.json();
            
            const endpoints = Array.isArray(data) ? data : [];
            displayEndpoints(endpoints);
        } catch (error) {
            console.error('Error loading endpoints:', error);
            endpointsList.innerHTML = '<div class="notice notice-error"><p>Error loading endpoints</p></div>';
        }
    }

    async function createEndpoint() {
        const routeValue = document.getElementById('route').value;
        const fullRoute = '/wp-json/wp-api-tester/v1/' + routeValue.replace(/^\/+/, '');
        
        const formData = {
            route: fullRoute,
            method: document.getElementById('method').value,
            permission: document.getElementById('permission').value,
            queryType: queryTypeSelect.value,
            queryData: queryTypeSelect.value === 'table' ? {
                table: databaseSelect.value,
                columns: Array.from(document.querySelectorAll('input[name="columns[]"]:checked'))
                    .map(checkbox => checkbox.value)
            } : {
                query: customQueryTextarea.value
            },
            response: JSON.parse(responseTextarea.value || '{}')
        };

        try {
            const response = await fetch(wpApiTester.apiUrl + '/endpoints', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiTester.nonce
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                form.reset();
                loadEndpoints();
                columnsContainer.style.display = 'none';
                responsePreview.textContent = '';
                customQueryContainer.style.display = 'none';
                tableQueryContainer.style.display = 'block';
            } else {
                const error = await response.json();
                alert(error.message || 'Error creating endpoint');
            }
        } catch (error) {
            console.error('Error creating endpoint:', error);
            alert('Error creating endpoint');
        }
    }

    function displayEndpoints(endpoints) {
        if (!Array.isArray(endpoints)) {
            console.error('Expected endpoints to be an array, got:', endpoints);
            endpointsList.innerHTML = '<div class="notice notice-error"><p>Invalid endpoints data</p></div>';
            return;
        }

        if (endpoints.length === 0) {
            endpointsList.innerHTML = '<div class="notice notice-info"><p>No endpoints created yet</p></div>';
            return;
        }

        const html = endpoints.map(endpoint => `
            <div class="endpoint-item">
                <h3>${escapeHtml(endpoint.method)} ${escapeHtml(endpoint.route)}</h3>
                <p>Permission: ${escapeHtml(endpoint.permission || 'none')}</p>
                <pre>${escapeHtml(JSON.stringify(endpoint.response, null, 2))}</pre>
            </div>
        `).join('');

        endpointsList.innerHTML = html;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});