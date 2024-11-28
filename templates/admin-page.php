<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>API Tester & Generator</h1>
    
    <div class="wp-api-tester-container">
        <div class="wp-api-tester-form">
            <h2>Create New Endpoint</h2>
            <form id="create-endpoint-form">
                <div class="form-group">
                    <label for="route">Route Path:</label>
                    <div class="route-input-group">
                        <span class="route-prefix">/wp-json/wp-api-tester/v1/</span>
                        <input type="text" id="route" name="route" required 
                               placeholder="my-custom-endpoint" class="regular-text">
                    </div>
                </div>

                <div class="form-group">
                    <label for="method">HTTP Method:</label>
                    <select id="method" name="method" required>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="permission">Permission Level:</label>
                    <select id="permission" name="permission" required>
                        <option value="none">No Permission Required</option>
                        <option value="user">Logged In User</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="query-type">Query Type:</label>
                    <select id="query-type" name="query-type">
                        <option value="table">Select from Table</option>
                        <option value="custom">Custom SQL Query</option>
                    </select>
                </div>

                <div id="table-query-container">
                    <div class="form-group">
                        <label for="database">Database Table:</label>
                        <select id="database" name="database" class="regular-text">
                            <option value="">Select a table...</option>
                        </select>
                    </div>

                    <div class="form-group" id="columns-container" style="display: none;">
                        <label>Select Columns:</label>
                        <div id="columns-list" class="columns-checkboxes"></div>
                    </div>
                </div>

                <div id="custom-query-container" style="display: none;">
                    <div class="form-group">
                        <label for="custom-query">Custom SQL Query:</label>
                        <textarea id="custom-query" name="custom-query" 
                                  class="large-text code" rows="4" 
                                  placeholder="SELECT * FROM wp_posts WHERE post_type = 'post' LIMIT 5"></textarea>
                        <p class="description">Note: For security, only SELECT queries are allowed.</p>
                    </div>
                </div>

                <div class="form-group">
                    <button type="button" id="test-query" class="button">
                        Test Query
                    </button>
                </div>

                <div class="form-group">
                    <label for="response">Response Preview:</label>
                    <pre id="response-preview" class="response-preview"></pre>
                    <textarea id="response" name="response" rows="10" 
                              class="large-text code" style="display: none;">{}</textarea>
                </div>

                <button type="submit" class="button button-primary">Create Endpoint</button>
            </form>
        </div>

        <div class="wp-api-tester-endpoints">
            <h2>Existing Endpoints</h2>
            <div id="endpoints-list"></div>
        </div>
    </div>
</div>