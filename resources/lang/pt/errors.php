<?php

/*
|--------------------------------------------------------------------------
| Traduzione in portoghese di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [
    'AUTHENTICATION_ERROR' => 'Acesso não autorizado',
    'SCAN_ERROR' => 'Erro de digitalização',
    'VIRUS_FOUND' => 'Vírus encontrado',
    'INVALID_FILE_EXTENSION' => 'Extensão de arquivo inválida',
    'MAX_FILE_SIZE' => 'O arquivo não pode exceder :max byte.',
    'INVALID_FILE_PDF' => 'Arquivo PDF inválido',
    'MIME_TYPE_NOT_ALLOWED' => 'Tipo de arquivo não permitido.',
    'INVALID_IMAGE_STRUCTURE' => 'Estrutura de imagem inválida',
    'INVALID_FILE_NAME' => 'Nome de arquivo inválido',
    'ERROR_GETTING_PRESIGNED_URL' => 'Erro ao obter URL pré-assinado',
    'ERROR_DURING_FILE_UPLOAD' => 'Erro durante o envio do arquivo',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Impossível salvar arquivo.',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Impossível criar pasta',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Impossível alterar permissões da pasta',
    'IMPOSSIBLE_SAVE_FILE' => 'Impossível salvar o arquivo',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Problema interno, assistência já foi alertada',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Erro durante a criptografia do nome do arquivo',
    'IMAGICK_NOT_AVAILABLE' => 'Problema interno, assistência já foi alertada',
    'JSON_ERROR_IN_DISPATCHER' => 'Erro JSON no dispatcher',
    'GENERIC_SERVER_ERROR' => 'Erro genérico do servidor, a equipe técnica foi informada',
    'FILE_NOT_FOUND' => 'Arquivo não encontrado',
    'UNEXPECTED_ERROR' => 'Erro inesperado',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Erro ao excluir o arquivo temporário local',

    'scan_error' => 'Erro de digitalização',
    'virus_found' => 'Vírus encontrado',
    'required' => 'O campo é obrigatório.',
    'file' => 'Ocorreu um erro ao enviar o arquivo.',
    'file_extension_not_valid' => 'Extensão de arquivo inválida',
    'mimes' => 'O arquivo deve ser do tipo: :values.',
    'max_file_size' => 'O arquivo não pode exceder :max byte.',
    'invalid_pdf_file' => 'Arquivo PDF inválido',
    'mime_type_not_allowed' => 'Tipo de arquivo não permitido.',
    'invalid_image_structure' => 'Estrutura de imagem inválida',
    'invalid_file_name' => 'Nome de arquivo inválido',
    'error_getting_presigned_URL' => 'Erro ao obter URL pré-assinado',
    'error_getting_presigned_URL_for_user' => 'Erro ao carregar arquivo',
    'error_during_file_upload' => 'Erro durante o envio do arquivo',
    'error_deleting_file' => 'Erro ao excluir arquivo',
    'upload_finished' => 'Upload finalizado',
    'some_errors' => 'alguns erros',
    'upload_failed' => 'upload falhou',
    'error_creating_folder' => 'Erro ao criar pasta',
    'error_changing_folder_permissions' => 'Erro ao alterar permissões da pasta',
    'local_save_failed_file_saved_to_external_disk_only' => 'Salvamento local falhou, arquivo salvo apenas no disco externo',
    'external_save_failed_file_saved_to_local_disk_only' => 'Salvamento externo falhou, arquivo salvo apenas no disco local',
    'file_scanning_may_take_a_long_time_for_each_file' => 'A verificação de arquivos pode levar muito tempo para cada arquivo',
    'all_files_are_saved' => 'Todos os arquivos estão salvos',
    'loading_finished_you_can_proceed_with_saving' => 'Carregamento concluído, você pode prosseguir com a gravação',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Carregamento concluído, você pode prosseguir com a gravação e a verificação',
    'im_uploading_the_file' => 'Estou enviando o arquivo',


    'exception' => [
        'NotAllowedTermException' => 'Termo não permitido',
        'MissingCategory' => 'É necessário inserir uma categoria.',
        'DatabaseException' => 'Ocorreu um erro de banco de dados',
        'ValidationException' => 'Ocorreu um erro de validação',
        'HttpException' => 'Ocorreu um erro HTTP',
        'ModelNotFoundException' => 'Modelo não encontrado',
        'QueryException' => 'Erro de consulta',
        'MintingException' => 'Erro durante o minting',
        'FileNotFoundException' => 'Arquivo não encontrado',
        'InvalidArgumentException' => 'Argumento inválido',
        'UnexpectedValueException' => 'Valor inesperado',
        'ItemNotFoundException' => 'Item não encontrado',
        'MultipleItemsFoundException' => 'Múltiplos itens encontrados',
        'LogicException' => 'Exceção lógica',
        'EntryNotFoundException' => 'Entrada não encontrada',
        'RuntimeException' => 'Erro de tempo de execução',
        'BadMethodCallException' => 'Chamada de método inválida',
        'LockTimeoutException' => 'Tempo limite de bloqueio',
        'InvalidIntervalException' => 'Intervalo inválido',
        'InvalidPeriodParameterException' => 'Parâmetro de período inválido',
        'EndLessPeriodException' => 'Período sem fim',
        'UnreachableException' => 'Exceção inalcançável',
        'InvalidTimeZoneException' => 'Fuso horário inválido',
        'ImmutableException' => 'Exceção imutável',
        'InvalidFormatException' => 'Formato inválido',
    ],
    'forbidden_term_warning' => "
        <div style=\"text-align: left;\">
            <p>Caro usuário,</p>
            </br>
            <p>O texto que você inseriu viola nossas normas e diretrizes da comunidade. Por favor, modifique o conteúdo e tente novamente.</p>
            </br>
            <p>Se você não entender o motivo pelo qual este termo é proibido, consulte as cláusulas do acordo que você aceitou no momento do registro.
            <p>Agradecemos sua compreensão e colaboração.</p>
            </br>
            <p>Atenciosamente,
            <br>
            A Equipe Frangette</p>
        </div>
    ",
    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            Consulte a página de regras da comunidade.
        </a>.',
    'forbiddenTermChecker_was_not_initialized_correctly' => 'ForbiddenTermChecker não foi inicializado corretamente',
    'table_not_exist' => 'A tabela não existe',
    'unique' => 'Este valor já está presente na sua biblioteca de traits.',
    'the_category_name_cannot_be_empty' => 'O nome da categoria não pode estar vazio',
    'nathing_to_save' => 'Nada a salvar',
    'an_error_occurred' => 'Opa! Desculpe, ocorreu um erro!',
    'error_number' => 'Número do erro:',
    'reason' => [
        'reason' => 'motivo',
        'wallet_not_valid' => 'Carteira inválida',
        'something_went_wrong' => 'Algo deu errado',
    ],
    'solution' => [
        'solution' => 'solução',
        'create_a_new_wallet_and_try_again' => 'Crie uma nova carteira e tente novamente',
        'we_are_already_working_on_solving_the_problem' => 'Já estamos trabalhando para resolver o problema',
    ],
    'min' => [
        'string' => 'O campo deve ter pelo menos :min caracteres.',
    ],
    'max' => [
        'string' => 'O campo deve ter no máximo :max caracteres.',
    ],
    'id_epp_not_found' => 'Id EPP não encontrado',
    'minting' => [
        'error_generating_token' => 'Erro ao gerar o token',
        'insufficient_wallet_balance' => 'Saldo insuficiente na carteira para comprar este EcoNFT',
        'error_during_save_the_metadataFile' => 'Erro ao salvar os metadados no arquivo',
        'error_during_save_the_metadata_on_database' => 'Erro ao salvar os metadados no banco de dados',
        'error_during_create_metadata_file' => 'Erro ao criar o arquivo de metadados',
        'error_during_save_the_buyer' => 'Erro ao salvar o comprador',
        'buyer_not_exist' => 'O comprador não existe',
        'this_wallet_does_not_belong_to_any_buyer' => 'Esta carteira não pertence a nenhum comprador',
        'seller_not_exist' => 'O vendedor não existe',
        'seller_owner_not_found' => 'O proprietário do vendedor não foi encontrado',
        'seller_wallet_address_not_found' => 'O endereço da carteira do vendedor não foi encontrado',
        'error_during_save_the_seller' => 'Erro ao salvar o vendedor',
        'error_during_save_the_buyer_transaction' => 'Erro ao salvar a transação do comprador',
        'error_during_the_saving_of_the_payment' => 'Erro ao salvar o pagamento',
        'error_during_save_the_natan' => 'Erro ao salvar os dados', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Erro ao salvar a transação',
        'seller_not_found' => 'Vendedor não encontrado',
        'error_during_the_minting' => 'Erro durante a criação',
        'error_uploading_file' => 'Erro ao enviar o arquivo',
        'insufficient_balance' => 'Saldo insuficiente',
        'eco_nft_not_found' => 'EcoNFT não encontrado',
        'no_traits_found' => 'Nenhum traço encontrado',
    ],
];
