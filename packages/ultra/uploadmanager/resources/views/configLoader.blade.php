<!--
 * This file is used to load the configuration values from the server side to the client side.
 * The values are loaded as a global variable in the window object.
 * The values are used in the Vue components.
-->

<script>
    window.defaultHostingService = @json(getDefaultHostingService());
    window.sendEmail            = "{{ config('error_constants.SEND_EMAIL') }}";
    window.devTeamEmailAddress  = "{{ config('app.devteam_email') }}";
    window.URLRedirectToCollection = "{{ config('app.redirect_to_collection') }}";
    window.errorDelTempLocalFileCode    = {{ config('error_constants.ERROR_DELETING_LOCAL_TEMP_FILE') }};
    window.errorDelTempExtFileCode      = {{ config('error_constants.ERROR_DELETING_EXT_TEMP_FILE') }};
    window.enableToCreateDirectory      = {{ config('error_constants.UNABLE_TO_CREATE_DIRECTORY') }};
    window.enableToChangePermissions    = {{ config('error_constants.UNABLE_TO_CHANGE_PERMISSIONS') }};
    window.settingAttempts      = "{{ config('app.setting_attempt') }}";
    window.uploadStatus         = "{{ __('label.upload_status') }}";
    window.waiting              = "{{ __('label.waiting') }}";
    window.uploadFiniscedText   = "{{ __('label.loading_finished_you_can_proceed_with_saving') }}";
    window.uploadAndScanText    = "{{ __('label.loading_finished_you_can_proceed_with_saving_and_scan') }}";
    window.btnDel               = "{{ __('label.delete') }}";
    window.selectFiles          = "{{ __('label.select_files') }}";
    window.startingUpload       = "{{ __('label.starting_uplad') }}";
    window.startingSaving       = "{{ __('label.starting_saving') }}";
    window.startingScan         = "{{ __('label.starting_scan') }}";
    window.scanError            = "{{ __('label.scan_error') }}";
    window.fileInfected         = "{{ __('label.file_infected') }}";
    window.oneOrMoreFilesInfected = "{{ __('label.one_or_more_files_were_found_infected') }}";
    window.loading              = "{{ __('label.loading') }}";
    window.saved                = "{{ __('label.saved') }}";
    window.uploaded             = "{{ __('label.uploaded') }}";
    window.successfully         = "{{ __('label.successfully') }}";
    window.of                   = "{{ __('label.of') }}";
    window.emogySad             = "{{ __('label.emogy_sad') }}";
    window.emogyHappy           = "{{ __('label.emogy_happy') }}";
    window.emogyAngry           = "{{ __('label.emogy_angry') }}";
    window.enableVirusScanning  = "{{ __('label.virus_scan_enabled') }}";
    window.disableVirusScanning = "{{ __('label.virus_scan_disabled') }}";
    window.fileSavedSuccessfullyTemplate = "{{ __('label.file_saved_successfully', ['fileCaricato' => ':fileCaricato']) }}";
    window.allFileIsSaved       = "{{ __('label.all_files_are_saved') }}";
    window.virusScanAdvise      = "{{ __('label.file_scanning_may_take_a_long_time_for_each_file') }}";
    window.uploadError          = "{{ __('label.error_uploading_file') }}";
    window.getPresignedUrlError = "{{ __('errors.error_getting_presigned_URL') }}";
    window.deleteFileError      = "{{ __('label.error_deleting_file') }}";
    window.loading              = "{{ __('label.loading') }}";
    window.someError            = "{{ __('label.some_errors') }}";
    window.completeFailure      = "{{ __('label.upload_failed') }}";
    window.temporaryFolder      = "{{ config('app.bucket_temp_file_folder') }}";
    window.allowedExtensions    = @json(config('AllowedFileType.collection.allowed_extensions'));
    window.allowedMimeTypes     = @json(config('AllowedFileType.collection.allowed_mime_types'));
    window.maxSize              = {{ config('AllowedFileType.collection.max_size') }}; // Max size in kbytes
    window.allowedExtensionsMessage          = "{{ __('label.file_extension_not_allowed') }}";
    window.allowedMimeTypesMessage           = "{{ __('label.file_type_not_allowed') }}";
    window.maxSizeMessage                    = "{{ __('label.file_size_exceeds') }}";
    window.invalidFileNameMessage            = "{{ __('label.invalid_file_name') }}";
    window.allowedExtensionsListMessage      = "{{ __('label.allowed_extensions') }}";
    window.allowedMimeTypesListMessage       = "{{ __('label.allowed_mime_types') }}";
    window.titleExtensionNotAllowedMessage   = "{{ __('label.title_extension_not_allowed') }}";
    window.titleFileTypeNotAllowedMessage    = "{{ __('label.title_file_type_not_allowed') }}";
    window.titleFileSizeExceedsMessage       = "{{ __('label.title_file_size_exceeds') }}";
    window.titleInvalidFileNameMessage       = "{{ __('label.title_invalid_file_name') }}";
    window.envMode                           = "{{ app()->environment() }}";
</script>
