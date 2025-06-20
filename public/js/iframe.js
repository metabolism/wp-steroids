(function() {
    /**
     *  Update iframe classes
     *  @see https://github.com/WordPress/gutenberg/issues/17854
     *  @see https://github.com/WordPress/gutenberg/issues/56831
     *  @see https://github.com/WordPress/gutenberg/issues/55947#issuecomment-1801105188
     */

    // This adds some parent body classes on the editor iframe
    function addBodyClassesToIframe() {

        const editorBodyEl = document.querySelector('.editor-styles-wrapper');

        if (!editorBodyEl)
            return;

        const adminBodyEl = window.parent.document.getElementsByTagName('body');
        const editorRootEl = document.querySelector('.is-root-container');

        const adminClasses = Array.from(adminBodyEl?.[ 0 ]?.classList).filter(name => name.startsWith('page-template-') || name.startsWith('preset-') || name.startsWith('style-') || name.startsWith('theme-'));

        if (editorRootEl && adminClasses) {

            adminClasses.forEach((adminClass) => {
                editorRootEl.classList.add(adminClass);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function (){
        setTimeout(addBodyClassesToIframe);
    });
})();