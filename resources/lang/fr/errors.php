<?php

/*
|--------------------------------------------------------------------------
| Traduzione in francese di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [
    'AUTHENTICATION_ERROR' => 'Accès non autorisé',
    'SCAN_ERROR' => 'Erreur de scan',
    'VIRUS_FOUND' => 'Virus trouvé',
    'INVALID_FILE_EXTENSION' => 'Extension de fichier non valide',
    'MAX_FILE_SIZE' => 'Le fichier ne peut pas dépasser :max octets.',
    'INVALID_FILE_PDF' => 'Fichier PDF invalide',
    'MIME_TYPE_NOT_ALLOWED' => 'Type de fichier non autorisé.',
    'INVALID_IMAGE_STRUCTURE' => 'Structure d\'image invalide',
    'INVALID_FILE_NAME' => 'Nom de fichier invalide',
    'ERROR_GETTING_PRESIGNED_URL' => 'Erreur lors de l\'obtention de l\'URL pré-signée',
    'ERROR_DURING_FILE_UPLOAD' => 'Erreur lors du téléchargement du fichier',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Impossible d\'enregistrer le fichier',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Erreur lors de la création du dossier',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Erreur lors de la modification des autorisations du dossier',
    'IMPOSSIBLE_SAVE_FILE' => 'Impossible d\'enregistrer le fichier',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Problème interne, l\'assistance a déjà été alertée',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Erreur lors du cryptage du nom de fichier',
    'IMAGICK_NOT_AVAILABLE' => 'Problème interne, l\'assistance a déjà été alertée',
    'JSON_ERROR_IN_DISPATCHER' => 'Erreur JSON dans le dispatcher',
    'GENERIC_SERVER_ERROR' => 'Erreur générique du serveur, l\'équipe technique a été informée',
    'FILE_NOT_FOUND' => 'Fichier non trouvé',
    'UNEXPECTED_ERROR' => 'Erreur générique du serveur, l\'équipe technique a été informée',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Erreur lors de la suppression du fichier temporaire local',

    'scan_error' => 'Erreur de scan',
    'virus_found' => 'Virus trouvé',
    'required' => 'Le champ est obligatoire.',
    'file' => 'Une erreur s\'est produite lors du téléchargement du fichier.',
    'file_extension_not_valid' => 'Extension de fichier non valide',
    'mimes' => 'Le fichier doit être de type : :values.',
    'max_file_size' => 'Le fichier ne peut pas dépasser :max octets.',
    'invalid_pdf_file' => 'Fichier PDF invalide',
    'mime_type_not_allowed' => 'Type de fichier non autorisé.',
    'invalid_image_structure' => 'Structure d\'image invalide',
    'invalid_file_name' => 'Nom de fichier invalide',
    'error_getting_presigned_URL' => 'Erreur lors de l\'obtention de l\'URL pré-signée',
    'error_getting_presigned_URL_for_user' => 'Erreur lors du chargement du fichier',
    'error_during_file_upload' => 'Erreur lors du téléchargement du fichier',
    'error_deleting_file' => 'Erreur lors de la suppression du fichier',
    'upload_finished' => 'Téléchargement terminé',
    'some_errors' => 'quelques erreurs',
    'upload_failed' => 'échec du téléchargement',
    'error_creating_folder' => 'Erreur lors de la création du dossier',
    'error_changing_folder_permissions' => 'Erreur lors de la modification des autorisations du dossier',
    'local_save_failed_file_saved_to_external_disk_only' => 'L\'enregistrement local a échoué, le fichier a été enregistré uniquement sur le disque externe',
    'external_save_failed_file_saved_to_local_disk_only' => 'L\'enregistrement externe a échoué, le fichier a été enregistré uniquement sur le disque local',
    'file_scanning_may_take_a_long_time_for_each_file' => 'L\'analyse des fichiers peut prendre beaucoup de temps pour chaque fichier',
    'all_files_are_saved' => 'Tous les fichiers sont enregistrés',
    'loading_finished_you_can_proceed_with_saving' => 'Le chargement est terminé, vous pouvez procéder à l\'enregistrement',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Le chargement est terminé, vous pouvez procéder à l\'enregistrement et à l\'analyse',
    'im_uploading_the_file' => 'Je télécharge le fichier',

    'exception' => [
        'NotAllowedTermException' => 'Terme non autorisé',
        'MissingCategory' => 'Vous devez entrer une catégorie.',
        'DatabaseException' => 'Une erreur de base de données s\'est produite',
        'ValidationException' => 'Une erreur de validation s\'est produite',
        'HttpException' => 'Une erreur HTTP s\'est produite',
        'ModelNotFoundException' => 'Modèle non trouvé',
        'QueryException' => 'Erreur de requête',
        'MintingException' => 'Erreur lors du minting',
        'FileNotFoundException' => 'Fichier non trouvé',
        'InvalidArgumentException' => 'Argument invalide',
        'UnexpectedValueException' => 'Valeur inattendue',
        'ItemNotFoundException' => 'Élément non trouvé',
        'MultipleItemsFoundException' => 'Plusieurs éléments trouvés',
        'LogicException' => 'Exception logique',
        'EntryNotFoundException' => 'Entrée non trouvée',
        'RuntimeException' => 'Erreur d\'exécution',
        'BadMethodCallException' => 'Appel de méthode incorrect',
        'LockTimeoutException' => 'Délai d\'attente de verrouillage',
        'InvalidIntervalException' => 'Intervalle invalide',
        'InvalidPeriodParameterException' => 'Paramètre de période invalide',
        'EndLessPeriodException' => 'Période sans fin',
        'UnreachableException' => 'Exception inatteignable',
        'InvalidTimeZoneException' => 'Fuseau horaire invalide',
        'ImmutableException' => 'Exception immuable',
        'InvalidFormatException' => 'Format invalide',
    ],
    'forbidden_term_warning' => "
        <div style=\"text-align: left;\">
            <p>Cher utilisateur,</p>
            </br>
            <p>Le texte que vous avez saisi viole nos règles et directives de la communauté. Veuillez modifier le contenu et réessayer.</p>
            </br>
            <p>Si vous ne comprenez pas pourquoi ce terme est interdit, veuillez vous référer aux clauses de l'accord que vous avez accepté lors de votre inscription.
            <p>Nous vous remercions de votre compréhension et de votre collaboration.</p>
            </br>
            <p>Cordialement,
            <br>
            L\'équipe de Frangette</p>
        </div>",

    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            Consultez la page des règles de la communauté.
        </a>.',
    'forbiddenTermChecker_was_not_initialized_correctly' => 'ForbiddenTermChecker n\'a pas été initialisé correctement',
    'table_not_exist' => 'La table n\'existe pas',
    'unique' => 'Cette valeur est déjà présente dans votre bibliothèque de traits.',
    'the_category_name_cannot_be_empty' => 'Le nom de la catégorie ne peut pas être vide',
    'nathing_to_save' => 'Rien à sauvegarder',
    'an_error_occurred' => 'Oops ! Désolé, une erreur s\'est produite !',
    'error_number' => 'Numéro d\'erreur :',
    'reason' => [
        'reason' => 'raison',
        'wallet_not_valid' => 'Portefeuille non valide',
        'something_went_wrong' => 'Quelque chose s\'est mal passé',
    ],
    'solution' => [
        'solution' => 'solution',
        'create_a_new_wallet_and_try_again' => 'Créez un nouveau portefeuille et réessayez',
        'we_are_already_working_on_solving_the_problem' => 'Nous travaillons déjà à résoudre le problème',
    ],
    'min' => [
        'string' => 'Le champ doit comporter au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ doit comporter au maximum :max caractères.',
    ],
    'id_epp_not_found' => 'ID EPP non trouvé',
    'minting' => [
        'error_generating_token' => 'Erreur lors de la génération du jeton',
        'insufficient_wallet_balance' => 'Solde insuffisant dans le portefeuille pour acheter cet EcoNFT',
        'error_during_save_the_metadataFile' => 'Erreur lors de l\'enregistrement des métadonnées dans le fichier',
        'error_during_save_the_metadata_on_database' => 'Erreur lors de l\'enregistrement des métadonnées dans la base de données',
        'error_during_create_metadata_file' => 'Erreur lors de la création du fichier de métadonnées',
        'error_during_save_the_buyer' => 'Erreur lors de l\'enregistrement de l\'acheteur',
        'buyer_not_exist' => 'L\'acheteur n\'existe pas',
        'this_wallet_does_not_belong_to_any_buyer' => 'Ce portefeuille n\'appartient à aucun acheteur',
        'seller_not_exist' => 'Le vendeur n\'existe pas',
        'seller_owner_not_found' => 'Le propriétaire du vendeur est introuvable',
        'seller_wallet_address_not_found' => 'L\'adresse du portefeuille du vendeur est introuvable',
        'error_during_save_the_seller' => 'Erreur lors de l\'enregistrement du vendeur',
        'error_during_save_the_buyer_transaction' => 'Erreur lors de l\'enregistrement de la transaction de l\'acheteur',
        'error_during_the_saving_of_the_payment' => 'Erreur lors de l\'enregistrement du paiement',
        'error_during_save_the_natan' => 'Erreur lors de l\'enregistrement des données', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Erreur lors de l\'enregistrement de la transaction',
        'seller_not_found' => 'Vendeur non trouvé',
        'error_during_the_minting' => 'Erreur lors de la création',
        'error_uploading_file' => 'Erreur lors du téléchargement du fichier',
        'insufficient_balance' => 'Solde insuffisant',
        'eco_nft_not_found' => 'EcoNFT non trouvé',
        'no_traits_found' => 'Aucun trait trouvé',
    ],
];
