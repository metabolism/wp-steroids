wpsEditor = wpsEditor || { config:{
   remove_core_block: '0',
   remove_plugin_block: '0'
} }

wpsEditor.class = {

    watchDataChanges(){

        let editor = wp.data.select('core/editor');

        let post = {
            post_title: editor.getEditedPostAttribute('title'),
            post_excerpt: editor.getEditedPostAttribute('excerpt'),
            thumbnail : editor.getEditedPostAttribute('featured_media'),
            status : editor.getEditedPostAttribute('status')
        }

        wp.data.subscribe(() => {

            let data = {
                status: editor.getEditedPostAttribute('status'),
                post_title: editor.getEditedPostAttribute('title'),
                post_excerpt: editor.getEditedPostAttribute('excerpt'),
                thumbnail_id : editor.getEditedPostAttribute('featured_media')
            }

            if( post.status !== data.status ) {

                Array.from(document.body.classList)
                    .filter(c => c.startsWith('post-status-'))
                    .forEach(c => document.body.classList.remove(c));

                document.body.classList.add('post-status-' + data.status);
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
        });

        window.addEventListener("load", this.removeCss)
    }
}

wpsEditor.class.init()

