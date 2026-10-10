setInterval(() => {
    if (!['INPUT', 'SELECT'].includes(document.activeElement.tagName)) {
        window.location.reload();
    }
}, 3000);
