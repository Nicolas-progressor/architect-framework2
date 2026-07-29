<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Architect Framework</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui.min.css">
    <style>
        body { margin: 0; background: #1a1a2e; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui { color: #e5e5e5; }
        .swagger-ui .info .title { color: #e5e5e5; }
        .swagger-ui .info { margin: 20px 0; }
        .swagger-ui .scheme-container { background: #16213e; box-shadow: none; }
        .swagger-ui .opblock-tag { color: #a5b4fc; }
        .swagger-ui .opblock .opblock-summary-description { color: #9ca3af; }
        .swagger-ui .opblock.opblock-get { background: rgba(97, 175, 254, 0.1); border-color: #61affe; }
        .swagger-ui .opblock.opblock-post { background: rgba(73, 204, 144, 0.1); border-color: #49cc90; }
        .swagger-ui .opblock.opblock-put { background: rgba(252, 161, 48, 0.1); border-color: #fca130; }
        .swagger-ui .opblock.opblock-delete { background: rgba(249, 62, 62, 0.1); border-color: #f93e3e; }
        .swagger-ui .opblock .opblock-section-header { background: #16213e; }
        .swagger-ui .opblock .opblock-section-header h4 { color: #e5e5e5; }
        .swagger-ui table thead tr td, .swagger-ui table thead tr th { color: #9ca3af; }
        .swagger-ui .parameter__name { color: #e5e5e5; }
        .swagger-ui .parameter__type { color: #9ca3af; }
        .swagger-ui .prop-type { color: #60a5fa; }
        .swagger-ui .btn { color: #e5e5e5; }
        .swagger-ui .response-col_status { color: #e5e5e5; }
        .swagger-ui .response-col_description { color: #9ca3af; }
        .swagger-ui .markdown p { color: #9ca3af; }
        .swagger-ui .model { color: #e5e5e5; }
        .swagger-ui .model-box { background: #16213e; }
        .swagger-ui .model-title { color: #e5e5e5; }
        .swagger-ui .prop-name { color: #60a5fa; }
        .swagger-ui .headers-wrapper { background: #16213e; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.17.14/swagger-ui-bundle.min.js"></script>
    <script>
        SwaggerUIBundle({
            url: '/docs/openapi.json',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
            ],
            layout: "BaseLayout",
            supportedSubmitMethods: ['get'],
        });
    </script>
</body>
</html>
