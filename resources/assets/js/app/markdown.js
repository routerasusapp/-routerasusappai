'use strict';

import { Converter } from 'showdown';

const hljs = require('highlight.js/lib/common');
hljs.configure({ ignoreUnescapedHTML: true });
hljs.safeMode();

const converter = new Converter({
    requireSpaceBeforeHeadingText: true,
    tables: true
});

/**
 * Escapes HTML special characters in a string
 * Source: https://stackoverflow.com/questions/1787322
 *
 * @param {string} text Raw text
 * @returns string Escaped HTML
 */
function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
}

function convertMarkdownToHtml(text) {
    // add ending code block tags when missing
    let code_block_count = (text.match(/```/g) || []).length;
    if (code_block_count % 2 !== 0) {
        text += "\n```";
    }

    // HTML-escape parts of text that are not inside ticks.
    // This prevents <?php from turning into a comment tag
    let escaped_parts = [];
    let code_parts = text.split("`");
    for (let i = 0; i < code_parts.length; i++) {
        if (i % 2 === 0) {
            escaped_parts.push(escapeHtml(code_parts[i]));
        } else {
            escaped_parts.push(code_parts[i]);
        }
    }
    let escaped_message = escaped_parts.join("`");

    // Convert Markdown to HTML
    let formatted_message = "";
    let code_blocks = escaped_message.split("```");
    for (let i = 0; i < code_blocks.length; i++) {
        if (i % 2 === 0) {
            // add two spaces in the end of every line
            // for non-codeblocks so that one-per-line lists
            // without markdown can be generated
            formatted_message += converter.makeHtml(
                code_blocks[i].trim().replace(/\n/g, "  \n")
            );
        } else {
            // convert Markdown code blocks to HTML
            formatted_message += converter.makeHtml(
                "```" + code_blocks[i] + "```"
            );
        }
    }

    return formatted_message;
}

export function markdownToHtml(text) {
    let html = convertMarkdownToHtml(text);

    let el = document.createElement('div');
    el.innerHTML = html;

    el.querySelectorAll('pre code').forEach((el) => {
        let text = el.innerText.trim();
        let h = hljs.highlightAuto(text);

        el.innerHTML = h.value;

        let actions = document.createElement('div');
        actions.classList.add('actions');

        let lang = document.createElement('span');
        lang.classList.add('lang');
        lang.innerText = h.language;

        actions.appendChild(lang);

        let copy = document.createElement('x-copy');
        copy.classList.add('copy');

        let icon = document.createElement('i');
        icon.classList.add('ti', 'ti-copy');

        copy.appendChild(icon);
        copy.setAttribute('data-copy', text);

        actions.appendChild(copy);

        el.closest('pre').prepend(actions);
    });

    return el.innerHTML;
}