import * as vscode from 'vscode';

export function activate(context: vscode.ExtensionContext) {
    console.log('Blueprint extension is now active!');

    // Регистрация команды для вставки элемента
    const insertElement = vscode.commands.registerCommand('blueprint.insertElement', async () => {
        const elementName = await vscode.window.showInputBox({
            prompt: 'Enter element name',
            placeHolder: 'e.g., navbar, footer, card'
        });

        if (elementName) {
            const editor = vscode.window.activeTextEditor;
            if (editor) {
                const position = editor.selection.active;
                editor.edit(editBuilder => {
                    editBuilder.insert(position, `{% element '${elementName}' %}`);
                });
            }
        }
    });

    // Регистрация команды для вставки переменной
    const insertVariable = vscode.commands.registerCommand('blueprint.insertVariable', async () => {
        const variableName = await vscode.window.showInputBox({
            prompt: 'Enter variable name',
            placeHolder: 'e.g., user.name, post.title'
        });

        if (variableName) {
            const editor = vscode.window.activeTextEditor;
            if (editor) {
                const position = editor.selection.active;
                editor.edit(editBuilder => {
                    editBuilder.insert(position, `{{ ${variableName} }}`);
                });
            }
        }
    });

    // Команда для оборачивания в блок
    const wrapInBlock = vscode.commands.registerCommand('blueprint.wrapInBlock', async () => {
        const blockName = await vscode.window.showInputBox({
            prompt: 'Enter block name',
            placeHolder: 'e.g., content, scripts, styles'
        });

        if (blockName) {
            const editor = vscode.window.activeTextEditor;
            if (editor) {
                const selection = editor.selection;
                const selectedText = editor.document.getText(selection);
                
                editor.edit(editBuilder => {
                    editBuilder.replace(selection, `{% block ${blockName} %}\n${selectedText}\n{% endblock %}`);
                });
            }
        }
    });

    context.subscriptions.push(insertElement, insertVariable, wrapInBlock);
}

export function deactivate() {
    console.log('Blueprint extension is now deactivated');
}
