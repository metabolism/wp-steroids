wpsEditor = wpsEditor || { config:{
   remove_core_block: '0',
   remove_plugin_block: '0'
} }

wpsEditor.class = {

    allowInterfaceResizeInterval : false,

    watchDataChanges(){

        let editor = wp.data.select('core/editor');

        let post = {
            post_title: editor.getEditedPostAttribute('title'),
            post_excerpt: editor.getEditedPostAttribute('excerpt'),
            thumbnail : editor.getEditedPostAttribute('featured_media')
        }

        wp.data.subscribe(() => {

            let data = {
                post_title: editor.getEditedPostAttribute('title'),
                post_excerpt: editor.getEditedPostAttribute('excerpt'),
                thumbnail_id : editor.getEditedPostAttribute('featured_media')
            }

            if( JSON.stringify(data) !== JSON.stringify(post) ){

                post = data;
                let blocks = wp.data.select( 'core/block-editor' ).getBlocks();

                if( blocks.length ){

                    let block = blocks[0];
                    let data = block.attributes.data ?? {}
                    data.post = post;

                    wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, data)
                }
            }
        });

        //remove block_editor_style-css from main editor if iFrame is enabled
        //Todo: find a better way to detect iFrame
        if( document.querySelector('[name="editor-canvas"]') ){

            let style = document.getElementById('block_editor_style-css')

            if( style ){

                let head = document.getElementsByTagName('head')[0];
                head.removeChild(style)
            }
        }

        let blocks = window.wp.data.select('core/block-editor').getBlocks();

        if( blocks ){

            blocks.forEach(function(block){

                if( block.attributes.mode === 'edit' )
                    wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, { mode: 'preview' });
            })
        }
    },

    unregisterBlockType(){

        let blocks = wp.blocks.getBlockTypes().map( ( block ) => block.name );

        blocks.forEach( ( block ) => {

            if ( 'remove_core_block' in wpsEditor.config && wpsEditor.config.remove_core_block && block.indexOf( 'core/' ) === 0 )
                wp.blocks.unregisterBlockType( block );

            if ( 'remove_plugin_block' in wpsEditor.config && wpsEditor.config.remove_plugin_block.length && wpsEditor.config.remove_plugin_block.indexOf(block) !== -1 )
                wp.blocks.unregisterBlockType( block );
        });
    },

    allowInterfaceResize(){

        let $sidebar = jQuery('.interface-interface-skeleton__sidebar');

        if( $sidebar.length ) {

            clearInterval(wpsEditor.class.allowInterfaceResizeInterval);

            $sidebar.width(localStorage.getItem('personal_sidebar_width'))

            $sidebar.resizable({
                handles: 'w',
                resize: function (event, ui) {
                    $sidebar.css({'left': 0});
                    localStorage.setItem('personal_sidebar_width', $sidebar.width());
                }
            });
        }
    },

    watchResize(){

        const targetNode = document.getElementById('editor');

        const config = {
            childList: true,
            subtree: true
        };

        const callback = (mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('interface-complementary-area__fill')) {
                            document.body.classList.add('has-sidebar');
                        }
                    });

                    mutation.removedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('interface-complementary-area__fill')) {
                            document.body.classList.remove('has-sidebar');
                        }
                    });
                }
            }
        };

        if( targetNode ){

            const observer = new MutationObserver(callback);
            observer.observe(targetNode, config);
        }
    },

    removeCss(){

        if( document.querySelector('[name="editor-canvas"]') ){

            let style = document.getElementById('block_editor_style-css')

            if( style ){

                let head = document.getElementsByTagName('head')[0];
                head.removeChild(style)
            }
        }
    },

    init(){

        if( typeof wp == 'undefined' )
            return;

        wp.domReady( () => {

            this.unregisterBlockType()
            this.watchDataChanges()
            this.watchResize()

            this.allowInterfaceResizeInterval = setInterval(this.allowInterfaceResize, 100);
        });

        window.addEventListener("load", this.removeCss)
    }
}

wpsEditor.class.init()

