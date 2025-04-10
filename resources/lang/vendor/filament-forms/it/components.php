<?php

return [

    'builder' => [

        'actions' => [

            'clone' => [
                'label' => 'Clona',
            ],

            'add' => [
                'label' => 'Aggiungi a :label',
            ],

            'add_between' => [
                'label' => 'Inserisci tra i blocchi',
            ],

            'delete' => [
                'label' => 'Elimina',
            ],

            'reorder' => [
                'label' => 'Sposta',
            ],

            'move_down' => [
                'label' => 'Sposta in basso',
            ],

            'move_up' => [
                'label' => 'Sposta in alto',
            ],

            'collapse' => [
                'label' => 'Comprimi',
            ],

            'expand' => [
                'label' => 'Espandi',
            ],

            'collapse_all' => [
                'label' => 'Comprimi tutti',
            ],

            'expand_all' => [
                'label' => 'Espandi tutti',
            ],

        ],

    ],

    'checkbox_list' => [

        'actions' => [

            'deselect_all' => [
                'label' => 'Deseleziona tutti',
            ],

            'select_all' => [
                'label' => 'Seleziona tutti',
            ],

        ],

    ],

    'file_upload' => [

        'editor' => [

            'actions' => [

                'cancel' => [
                    'label' => 'Annulla',
                ],

                'drag_crop' => [
                    'label' => 'Modalità trascinamento "ritaglio"',
                ],

                'drag_move' => [
                    'label' => 'Modalità trascinamento "sposta"',
                ],

                'flip_horizontal' => [
                    'label' => 'Capovolgi immagine orizzontalmente',
                ],

                'flip_vertical' => [
                    'label' => 'Capovolgi immagine verticalmente',
                ],

                'move_down' => [
                    'label' => 'Sposta immagine in basso',
                ],

                'move_left' => [
                    'label' => 'Sposta immagine a sinistra',
                ],

                'move_right' => [
                    'label' => 'Sposta immagine a destra',
                ],

                'move_up' => [
                    'label' => 'Sposta immagine in alto',
                ],

                'reset' => [
                    'label' => 'Reimposta',
                ],

                'rotate_left' => [
                    'label' => 'Ruota immagine a sinistra',
                ],

                'rotate_right' => [
                    'label' => 'Ruota immagine a destra',
                ],

                'set_aspect_ratio' => [
                    'label' => 'Imposta rapporto a :ratio',
                ],

                'save' => [
                    'label' => 'Salva',
                ],

                'zoom_100' => [
                    'label' => 'Zoom immagine al 100%',
                ],

                'zoom_in' => [
                    'label' => 'Zoom avanti',
                ],

                'zoom_out' => [
                    'label' => 'Zoom indietro',
                ],

            ],

            'fields' => [

                'height' => [
                    'label' => 'Altezza',
                    'unit' => 'px',
                ],

                'rotation' => [
                    'label' => 'Rotazione',
                    'unit' => 'gradi',
                ],

                'width' => [
                    'label' => 'Larghezza',
                    'unit' => 'px',
                ],

                'x_position' => [
                    'label' => 'X',
                    'unit' => 'px',
                ],

                'y_position' => [
                    'label' => 'Y',
                    'unit' => 'px',
                ],

            ],

            'aspect_ratios' => [

                'label' => 'Rapporti',

                'no_fixed' => [
                    'label' => 'Libero',

                ],

            ],

            'svg' => [

                'messages' => [
                    'confirmation' => 'La modifica dei file SVG non è consigliata in quanto può comportare una perdita di qualità durante il ridimensionamento.\n Sei sicuro di voler continuare?',
                    'disabled' => 'La modifica dei file SVG è disabilitata in quanto può comportare una perdita di qualità durante il ridimensionamento.',
                ],

            ],

        ],

    ],

    'key_value' => [

        'actions' => [

            'add' => [
                'label' => 'Aggiungi riga',
            ],

            'delete' => [
                'label' => 'Elimina riga',
            ],

            'reorder' => [
                'label' => 'Riordina riga',
            ],

        ],

        'fields' => [

            'key' => [
                'label' => 'Chiave',
            ],

            'value' => [
                'label' => 'Valore',
            ],

        ],

    ],

    'markdown_editor' => [

        'toolbar_buttons' => [
            'attach_files' => 'Allega file',
            'blockquote' => 'Citazione',
            'bold' => 'Grassetto',
            'bullet_list' => 'Elenco puntato',
            'code_block' => 'Blocco di codice',
            'heading' => 'Intestazione',
            'italic' => 'Corsivo',
            'link' => 'Link',
            'ordered_list' => 'Elenco numerato',
            'redo' => 'Ripristina',
            'strike' => 'Barrato',
            'table' => 'Tabella',
            'undo' => 'Annulla',
        ],

    ],

    'radio' => [

        'boolean' => [
            'true' => 'Si',
            'false' => 'No',
        ],

    ],

    'repeater' => [

        'actions' => [

            'add' => [
                'label' => 'Aggiungi a :label',
            ],

            'add_between' => [
                'label' => 'Inserisci tra',
            ],

            'delete' => [
                'label' => 'Elimina',
            ],

            'clone' => [
                'label' => 'Clona',
            ],

            'reorder' => [
                'label' => 'Sposta',
            ],

            'move_down' => [
                'label' => 'Sposta in basso',
            ],

            'move_up' => [
                'label' => 'Sposta in alto',
            ],

            'collapse' => [
                'label' => 'Comprimi',
            ],

            'expand' => [
                'label' => 'Espandi',
            ],

            'collapse_all' => [
                'label' => 'Comprimi tutti',
            ],

            'expand_all' => [
                'label' => 'Espandi tutti',
            ],

        ],

    ],

    'rich_editor' => [

        'dialogs' => [

            'link' => [

                'actions' => [
                    'link' => 'Collega',
                    'unlink' => 'Scollega',
                ],

                'label' => 'URL',

                'placeholder' => 'Inserisci un URL',

            ],

        ],

        'toolbar_buttons' => [
            'attach_files' => 'Allega file',
            'blockquote' => 'Citazione',
            'bold' => 'Grassetto',
            'bullet_list' => 'Elenco puntato',
            'code_block' => 'Blocco di codice',
            'h1' => 'Titolo',
            'h2' => 'Intestazione',
            'h3' => 'Sottotitolo',
            'italic' => 'Corsivo',
            'link' => 'Link',
            'ordered_list' => 'Elenco numerato',
            'redo' => 'Ripristina',
            'strike' => 'Barrato',
            'underline' => 'Sottolineato',
            'undo' => 'Annulla',
        ],

    ],

    'select' => [

        'actions' => [

            'create_option' => [

                'modal' => [

                    'heading' => 'Crea',

                    'actions' => [

                        'create' => [
                            'label' => 'Crea',
                        ],

                        'create_another' => [
                            'label' => 'Crea & creane un altro',
                        ],

                    ],

                ],

            ],

            'edit_option' => [

                'modal' => [

                    'heading' => 'Modifica',

                    'actions' => [

                        'save' => [
                            'label' => 'Salva',
                        ],

                    ],

                ],

            ],

        ],

        'boolean' => [
            'true' => 'Si',
            'false' => 'No',
        ],

        'loading_message' => 'Caricamento...',

        'max_items_message' => 'Solo :count possono essere selezionati.',

        'no_search_results_message' => 'Nessuna opzione corrisponde alla tua ricerca.',

        'placeholder' => "Seleziona un'opzione",

        'searching_message' => 'Ricerca...',

        'search_prompt' => 'Digita per cercare...',

    ],

    'tags_input' => [
        'placeholder' => 'Nuovo tag',
    ],

    'wizard' => [

        'actions' => [

            'previous_step' => [
                'label' => 'Precedente',
            ],

            'next_step' => [
                'label' => 'Successivo',
            ],

        ],

    ],

    'text_input' => [

        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'email' => 'Email',
        'password' => 'Password',
        'address' => 'Indirizzo',
        'phone' => 'Telefono',
        'account' => 'Gestione utente',

        'utilities' => [

            'label' => [
                'utility' => 'Utility',
                'utilities' => 'Utilities',
                'utilities_file' => 'Utilities files',
                'utility_name' => 'Nome dell\'utility',
                'description' => 'Descrizione',
                'utility_type' => 'Tipo di Utility',
                'usage_limit' => 'Limite di Utilizzo',
                'Expiration_date' => 'Data di Scadenza',
                'utility_bind_EGI' => 'L\'utility è vincolata con EGI?',

            ],
        ],

        'traits' => [

            'label' => [
                'trait' => 'Trait',
                'traits' => 'Traits',
                'category' => 'Categoria',
                'categories' => 'Categorie',
                'key' => 'Chiave',
                'keys' => 'Chiavi',
                'create_new_key' => 'Crea nuova chiave',
                'value' => 'Valore',
                'assign_value_to_trait' => 'Assegna un valore al Trait',
                'values' => 'Valori',
                'category_key' => 'Categoria - chiave',
                'trait_name' => 'Nome del Trait',
                'trait_description' => 'Descrizione del Trait',
                'trait_type' => 'Tipo di Trait',
                'assign_traits_to_egi' => 'Assegna i Traits agli EGI',
            ],
        ],
        'collection' => [
            'label' => [
                'team_members' => 'Membri del Team',
            ],
            'panel_label_single' => 'Collezione',
            'panel_label_plural' => 'Collezioni',
        ],

        'collection_wallet' => [

            'label' => [
                'wallet' => 'Wallet',
                'wallets' => 'Wallets',
                'wallet_email' => 'Email del Wallet',
                'number_of_wallets_in_the_collection' => 'Numero di Wallet nella Collezione',
                'wallet_address' => 'Indirizzo del Wallet',
                'royalty_mint' => '% Royalty Mint',
                'royalty_rebind' => '% Royalty Rebind',
                'status' => 'Tipo di Utility',
                'role' => 'Ruolo',

            ],
            'panel_label' => 'Wallet della collezione',
        ],

        'collection_team' => [
            'label' => [
                'team_members' => 'Membri del Team',
            ],
            'panel_label' => 'Membri del team',
        ],

        'AddEGI' => [
            'panel_label' => 'Modifica EGI',

        ],

    ],

];
