<?php

namespace Ultra\UploadManager\Controllers;

use App\Contracts\UpdatesDataItem;
use App\Models\Team;
use App\Models\Teams_item;
use App\Models\TraitKeyCommon;
use App\Models\TraitsItem;
use App\Models\TraitsValueUser;
use App\Models\User;
use App\Traits\HasNotifications;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Routing\Controller;

class ItemsEdit extends Component implements HasForms
{
    #[Url]
    public $itemId;

    public $utility_files;
    public $team_name;
    public $utility;
    public $teamId;
    public $itemChoseInSelectforBind;
    public $teamItem;
    public $team;
    public $itemType;
    public $saved = false;
    public $fileImage_id;
    public $confirmingItemDelete;
    public $itemIdBeingTransfer;
    public $openEditFlag;
    public $imagetitle;
    public $filename;
    public $fileMedia;
    public $type;
    public $paired;
    public $dateCreation;
    public $confirmFileRemoval = false;
    public $confirmUtilityRemoval = false;
    public $ongoingDrop = false;
    public ?array $data = [];

    public $item;
    public $current_team;
    public $state = [];
    public $items = [];
    public $cardType;
    public $show_traits_button;
    public $haveUtilities;
    public $extTransfer;
    public $emailExternalTransfer;
    public $itemIdBeingRemoved;
    public $confirmItemRemoval;
    public $confirmingItemTransfer;
    public $confirmingItemInternalTransfer;
    public $teamNameTo;
    public $confirmingTraitsRemoved = false;
    public $traitsIdBeingRemoved;
    public $traitKey;
    public $traitValue;
    public $isDirty = false;
    public $user_id_receiver;
    public $fileImage;
    public $hashFile;
    public $notificationAction = '';
    public $msgError;
    public $codError;
    public $title;
    public $collectionName;
    protected $selectedLanguage;
    protected $languages = [];
    public $traitsValueUsersId;
    public $hasTraits;
    public $openBindedTraits = false;
    protected $channel = 'collections';

    public Request $request;

    use HasNotifications;
    use InteractsWithForms;

    public function mount()
    {

        Log::channel($this->channel)->info('Classe: ItemEdit. Metodo: MOUNT. Action: DENTRO IL METODO');

        // Verifico se questo EGI ha dei traits abbinati
        $this->hasTraits = TraitsItem::where('teams_items_id', $this->itemId)->count() > 0 ? 1 : 0;
        Log::channel($this->channel)->info('Classe: ItemEdit. Metodo: MOUNT. Action: $this->hasTraits: '.$this->hasTraits);

        // creo il recordset del singolo item
        $this->item = Teams_item::findOrFail($this->itemId);

        // Riempi il form con i dati dell'item
        $this->form->fill($this->item->toArray());

        // Rendo pubblico il titolo dell'item per poterlo usare nella vista
        $this->title = $this->item->title;

        // rilevo la lingua corrente impostata in config/app
        $this->selectedLanguage = App::getLocale();

        // leggo le lingue gestite
        $this->languages = config('app.languages');

        // flag per la finestra modale del trasferimento esterno dell'item
        $this->extTransfer = false;

        // rilevo la collection corrente (ricorda che la tabella team è la tabella delle collection)
        $this->current_team = Auth::user()->currentTeam;

        // stabilisce il tipo di item che devo mostrare in questo caso edit
        $this->cardType = 'edit';

        /*
            Questo mi permette di visualizzare la voce del menù della sidebar che in questo caso
            deve essere "back to collection"
        */
        // $this->openEditFlag = $origin;

        // permette la visualizzazione del bottone della gestione traits
        $this->show_traits_button = true;

        /*
            determina se l'item deve essere pubblicato o meno, questo permette di visualizzare correttamente
            la voce: Pulished o Not published
        */
        // $this->dontShow = $this->item->dontShow;

        $this->collectionName = $this->current_team->name;

        // Serve a popolare la select delle cover nel caso che l'item sia un audio
        $this->items = Teams_item::where('team_id', $this->current_team->id)
            ->where('bind', 0)
            ->where('type', 'image')
            ->orderby('position')
            ->get();

        // Creo l'array delle property per popolare i campi e per gestire il CRUD con livewire
        $this->state = $this->item->withoutRelations()->toArray();

        // if ($this->state['price']) {
        //     $this->state['price'] = number_format($this->state['price'], 2, ',', '.');
        // }

        // se il campo util_description non è nullo si assume che ci sia un utility connessa all'item
        // $this->haveUtilities = $this->item->util_description ? 1 : 0;

        // memorizzo il percorso e il nome del file senza estensione webp, serve nel caso si faccia una update
        // in questo caso si usa questa variabile, altrimenti salva il file con l'estensione e non va bene
        // $this->hashFile = $this->item->hash_file;

        // passo il file da visualizzare.
        $this->fileImage = $this->item->file_image;

        // $this->teamId = Auth::user()->current_team->id;
        // $this->selectItemtype();

    }

    public function form(Form $form): Form
    {
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: Form. Action: all\'interno!');

        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('label.edit_item.title'))
                    ->placeholder(__('label.edit_item.title')),
                MarkdownEditor::make('description')
                    ->label(__('label.edit_item.description'))
                    ->placeholder(__('label.edit_item.description')),
                TextInput::make('price')
                    ->label(__('label.floor_price').' ALGO'),
                DatePicker::make('creation_date')
                    ->label(__('label.edit_item.date_of_creation'))
                    ->nullable(),
                Toggle::make('show')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-user')
                    ->label(__('label.edit_item.publish')),
                TextInput::make('position')
                    ->label(__('label.position'))
                    ->numeric(),

            ])
            ->statePath('data')
            ->model(Teams_item::class);

    }

    public function openBindTraits($itemId)
    {
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: openBindedTraits. Action: $itemId: '.$itemId);
        $this->itemId = $itemId;
        $this->dispatch('closeTraitsComponent');
        $this->openBindedTraits = ! $this->openBindedTraits;
    }

    public function OpenTraitPage()
    {
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: OpenTraitPage. Action: all\'interno!');

        // il metodo toggleTraitsComponent() si trova nel file app/Filament/EditEgi/Pages/EditEgiPage.php
        $this->dispatch('toggleTraitsComponent');
        $this->openBindedTraits = false;

    }

    #[On('undo')]
    public function undo()
    {
        $this->traitsValueUsersId = null;

    }

    #[On('bindTraitToEgi')]
    public function bindTraitToEgi($event)
    {
        // La tua logica qui
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: bindTraitToEgi. Action: $event: '.$event);
        $this->traitsValueUsersId = intval($event);
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: bindTraitToEgi. Action: $this->itemId: '.$this->itemId);

        // notifico con SweetAlert2 che l'operazione è andata a buon fine
        $this->dispatch('sureMergeTraitToEGI', [
            'confirmButtonText' => __('traits.yes_match_it'),
            'cancelButtonText' => __('label.cancel'),
            'title' => __('traits.apply_traits'),
            'message' => __('traits.sure_you_what_match_this_trait'),
            'result_title' => __('traits.trait_added'),
            'result_message' => __('traits.the_trait_was_matched'),
        ]);
    }

    /**
     * Questo metodo chiamato via brodchast da BindedTraits.php per nascondere il bottone di apertura
     * della finestra dell'elenco dei traits abbinati all'EGI. Il componente è appunto BindedTraits,
     * da questo posso eliminare i traits abbinati e se sono arrivato a eliminare l'ultimo il
     * pulsante badge deve scomparire.
     *
     * Allo stesso tempo, se non ci sono più traits abbinati, devo nascondere anche la finestra
     * dei traits abbinati
     */
    #[On('refreshEgiItems')]
    public function refreshEgiItems()
    {
        if (TraitsItem::where('teams_items_id', $this->itemId)->count() == 0) {
            $this->hasTraits = false;
            $this->openBindedTraits = false;
        }
    }

    /**
     * Questo metodo viene chiamato quando l'utente trascina un trait sopra un EGI
     * queste sono le variabili utilizzate:
     * var $this->traitsValueUsersId = id del trait
     * var $this->itemId = id dell'item
     * */
    #[On('mergeTraitToEGI')]
    public function mergeTraitToEGI()
    {

        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: $this->itemId: '.$this->itemId);
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: $this->traitsValueUsersId: '.$this->traitsValueUsersId);
        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: $this->current_team->id: '.$this->current_team->id);

        try {
            // Verifico che il trait non sia già stato abbinato all'EGI
            $trait = TraitsItem::where('traits_value_users_id', $this->traitsValueUsersId)
                ->where('teams_items_id', $this->itemId)
                ->first(); // first

            if ($trait === null) {
                // Se il traits non è stato abbinato all'EGI, allora lo abbiniamo

                Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: Auth::id(): '.Auth::id());

                // Creo il record nella tabella traits_items (tabella pivot tra items e traits_value_users)
                $trait = new TraitsItem;
                // scrivo l'id della tabella items
                $trait->teams_items_id = $this->itemId;
                // scrivo l'id della tabella traits_value_users
                $trait->traits_value_users_id = $this->traitsValueUsersId;
                // scrivo l'id del team
                $trait->team_id = $this->current_team->id;
                // scrivo l'id dell'utente
                $trait->user_id = Auth::id();

                // Adesso devo trovare il value del trait che l'utente ha scelto per abbinarlo all'EGI
                // Per capire meglio, si tratta di "valore" nella coppia chiave/valore
                // Cerco il record nella tabella traits_value_users, usando l'id del trait scelto dall'utente
                $traitValueUser = TraitsValueUser::find($this->traitsValueUsersId);

                // Poi mi occorre trovare la key corrispondente a questo traitValueUser
                // quindi leggo il traits_key_common_id per cercare il record della chiave della coppia chiave/valore
                $traits_key_common_id = $traitValueUser->traits_key_common_id;

                // Leggo il record della tabella traits_key_common corrispondente all'id della key
                $traitKeyCommon = TraitKeyCommon::find($traits_key_common_id);

                Log::channel('traits')->info('$traits_key_common_id: '.$traitKeyCommon->key_en);
                Log::channel('traits')->info('$value: '.$traits_key_common_id);

                // Log::channel('traits')->info('$this->languages'.$this->languages);

                foreach ($this->languages as $lang) {

                    $keyField = 'key_'.$lang;
                    $valueField = 'value_'.$lang;

                    $trait->{$keyField} = $traitKeyCommon->{$keyField};
                    $trait->{$valueField} = $traitValueUser->{$valueField};

                    Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: assign:'.$keyField.' => '.$traitKeyCommon->{$keyField});
                    Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: assign:'.$valueField.' => '.$traitValueUser->{$valueField});

                }

                $trait->save();

                $this->notificationAction = 'success';
                $this->notitications();

                if (TraitsItem::where('teams_items_id', $this->itemId)->count() > 0) {
                    $this->hasTraits = true;
                }

            }

        } catch (\Exception $e) {
            // Questo blocco può catturare altri tipi di eccezioni generiche
            // Log dell'errore o gestione dell'eccezione

            Log::channel('traits')->info('Classe: ItemsEdit. Metodo: mergeTraitToEGI. Action: error:'.$e->getMessage());
            // Potresti voler aggiungere un messaggio di errore generico per l'utente
        }

    }

    protected function mergeIds($sourceId, $destinationId)
    {
        // Logica per unire gli ID
        // ...
        Log::channel('upload')->info('sourceId: '.$sourceId);
    }

    public function create(): void
    {

        Log::channel('traits')->info('Classe: ItemsEdit. Metodo: create. Action: all\'interno');

        //Trova il record esistente o fallisci
        try {

            // Salva il record
            $this->item->update($this->form->getState());
            Log::channel('traits')->info('Classe: ItemsEdit. Metodo: create. Action: dopo update');

            $this->notitications('success');

        } catch (\Exception $e) {

            if (session('authorized') === false) {
                $this->notificationAction = 'unautorized_create';
                $this->notitications();

                return;

            } else {

                $this->msgError = __('filament-forms::validation.notifications.generic_error');
                $this->codError = 500;

                return;
            }

            Log::channel('upload')->error('Errore: '.$e->getMessage());

        }

        Log::channel('upload')->info('metodo edit eseguito con successo: ');

    }

    public function updated($field)
    {
        $this->edit();

    }

    public function emitRememberSaved()
    {
        // $this->dispatch('rememberSaved');
        $this->edit();
    }

    /**
     * @var fileCoverId è l'id del record dell'immagine che deve essere usata come cover
     * @var itemId è l'id del record dell'item che deve essere abbinato all'immagine
     * @var file è il percorso dell'immagine che deve essere visualizzata
     *
     * Questa funzione è chiamata da una chiamata AJAX nel file cover-select.blade.php
     *
     * Spiegazione della funzione bind()
     * Leggo tutti i dati passati dalla chiamata Ajax: path, fileCoverId, itemId
     *
     * Item = è l'EGI che necessita di una cover
     * Cover = è l'immagine che deve essere abbinata all'item
     * Nella vista è presente la select fileImage che contiene tutte le immagini della collection che ancora non sono state usate come cover
     * (solo le immagini) queste vengono usate come cover per gli EGI di tipo audio, video o ebook.
     * Questa funzione riceve l'id dell'immagine che dovrà diventare la cover e l'id dell'item che dovrà essere abbinato a questa cover.
     *
     * Occorre tenere presente che nel database non c'è il percorso dell'immagine, ma solo l'id del record dell'immagine.
     * L'immagine è salvata sul disco di Digital Ocean e la chiave per accedervi è composta da config('app.bucket_path_file_folder'),
     * config('app.bucket_root_file_folder'), lo user id e il team id e l'id del record dell'immagine.
     *
     * Questa funzione riceve l'id dell'immagine che dovrà diventare la cover e l'id dell'item che dovrà essere abbinato a questa cover.
     * Devo fare due operazioni:
     * 1. Settare il campo bind del record dell'immagine a true per escluderlo dalla select e indicare che il presente record è stato usato come cover
     * 2. Settare il campo key_file del record dell'item corrente con l'id del record dell'immagine che è stato usato come cover
     * Questo costituisce il bind della cover all'item.
     */
    public function bind(Request $request) // $id è l'id dell'item selezionato nella select
    {

        // A causa di alcuni bug di livewire 3, devo usare la request per recuperare i dati inviati
        // tramite una chiamata AJAX nel file cover-select.blade.php

        if ($request->input('fileId') == 'empty') {
            // se l'utente seleziona l'opzione vuota della select
            return;
        }

        $fileCoverId = $request->input('fileId');
        $itemId = $request->input('itemId');

        // Id dell'item corrente
        Log::channel('bind_unbind_cover')->info('$itemId: '.$itemId);

        // Id del record della cover selezionata nella selct
        Log::channel('bind_unbind_cover')->info('fileCoverId: '.$fileCoverId);

        /**
         * --- NOTA BENE ---
         * A CAUSA DI UN BUG DI LIVEWIRE 3 NON POSSO USARE L'EVENTO dispatch. Se lo utilizzo, l'ascoltartore nel file
         * nello script Javascript nel file cover-select.blade.php, che viene chiamato da qui, per qualche ragione non
         * permette l'aggiornamento degli elementi HTML sulla vista,
         * quindi l'istruzione image.src = path; non funziona!
         *
         * Quindi devo usare una funzione javascript per visualizzare l'anteprima dell'immagine selezionata nella select.
         * e dopo aver fatto la preview devo chiamare la funzione bind() per salvare i dati. (questo metodo)
         *
         * Per chiamare il presente metodo bind, uso una chiamata AJAX
         */

        // Emit un evento Livewire per anteprima dell'immagine selezionata nella select
        // $file = asset('images/default/COVER.png');
        // $this->dispatch('coverPreview', $file);

        // *** NOTA BENE *** Questo sopra è l'evento che non posso usare causa del BUG.
        // Se un gionro il bug dovesse essere risolto sarebbe preferibile usare la tecnologia degli eventi livewire piuttosto
        // che questo casino che mi sono dovuto inventare per far funzionare questa procedura di anteprima/salvataggio

        // $this->dispatch('preview');

        // Trovo il record del file che deve essere utilizzato come cover
        $file_cover = teams_item::find($fileCoverId);
        Log::channel('bind_unbind_cover')->info(json_encode($file_cover));

        // Verifico di avere un recordset valido
        if (empty($file_cover)) {
            Log::channel('bind_unbind_cover')->info('Record dell file cover non creato');
        } else {
            // Setto il campo bind a true per escluderlo dalla select e indicare che il presente record
            // è stato usato come cover
            $file_cover->bind = true;
            $file_cover->save();
        }

        // L'id del record dell'EGI corrente, l'item corrente della vista, è lo styesso valore presente anche nella uri stessa.
        Log::channel('bind_unbind_cover')->info('$itemId: '.$itemId);

        // Trovo il record dell'item corrente
        $current_egi = teams_item::find($itemId);
        Log::channel('bind_unbind_cover')->info('$current_egi: '.json_encode($current_egi));

        // Verifico di avere un recordset valido
        if (empty($current_egi)) {
            Log::channel('bind_unbind_cover')->info('current_egi è vuoto');
        } else {
            // Scrivo l'ID della cover nel campo key_file del record dell'item corrente, che è quello utilizzato per
            // visualizzare l'anteprima dell'immagine
            $current_egi->key_file = $fileCoverId;
            // Inidico che il record è stato unito a una cover, in questo modo verrà visualizzato il bottone
            // per fare unbind anizhcé la select
            $current_egi->paired = true;
            $current_egi->save();
        }

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);

    }

    public function unpair(Request $request)
    {

        Log::channel('bind_unbind_cover')->info('UNPAIR');

        // A causa di alcuni bug di livewire 3, devo usare la request per recuperare i dati inviati
        // tramite una chiamata AJAX nel file cover-select.blade.php
        $itemId = $request->input('itemIdEGI');

        // Id dell'item corrente
        Log::channel('bind_unbind_cover')->info('$itemId: '.$itemId);

        // Trovo il record dell'item corrente
        $current_egi = teams_item::find($itemId);
        Log::channel('bind_unbind_cover')->info('$current_egi: '.json_encode($current_egi));

        // Verifico di avere un recordset valido
        if (empty($current_egi)) {
            Log::channel('bind_unbind_cover')->error('current_egi è vuoto');
        } else {

            // trovo l'id della cover nel campo key_file del record dell'item corrente
            $fileCoverId = $current_egi->key_file;

            // creao il record del file che è stato usato come cover
            $file_cover = teams_item::find($fileCoverId);

            // Verifico di avere un recordset valido
            if (empty($file_cover)) {
                Log::channel('bind_unbind_cover')->error('Record dell file cover non creato');
            } else {
                // resetto il campo bind a false per riportarlo disponibile per altre cover
                $file_cover->bind = false;
                $file_cover->save();
            }

            // Scrivo l'ID 0 della cover de default
            $current_egi->key_file = 0;
            // E riporto a null il campo paired, in questo modo verrà visualizzata la select
            $current_egi->paired = null;
            $current_egi->save();

            Log::channel('bind_unbind_cover')->info('UNPAIR FINISH OK');
        }

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo
        di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);

    }

    public function delete()
    {

        try {

            Teams_item::find($this->itemIdBeingRemoved)->delete();
            $this->dispatch('deleted');

            return redirect(url('/dashboard/collection/item_upload'));

        } catch (\Exception $e) {
            // Mostra un messaggio di errore personalizzato all'utente
            $this->confirmItemRemoval = true;
            $this->dispatch('errore');

        }

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo
        di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);

    }

    public function edit()
    {

        // Risolve l'istanza di UpdatesDataItem dal container di servizi
        $updater = app(UpdatesDataItem::class);

        $updater->update(
            $this->item,
            $this->state,
        );

        $this->isDirty = false;
        $this->dispatch('saved');

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo
        di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);

    }

    public function setDirty()
    {
        $this->isDirty = true;
        $this->dispatch('rememberSaved');
    }

    public function externalTransfer($teams_item_id_to_transfer)
    {

        // dd('sono dentro la funzione');

        // trovo l'id dell'utente destinatario tramite l'indirizzo mail
        $rcd_user_receiver = User::where('email', $this->emailExternalTransfer)->first();

        // verifico che l'utente esista
        if (isset($rcd_user_receiver->id)) {
            $user_id_receiver = $rcd_user_receiver->id;
        } else {
            $this->addError('error', __("non esiste un utente con l\'indirizzo email: $this->emailExternalTransfer"));

            return;
        }

        // trovo l'item che deve essere trasferito al destinatario
        $rcd_item_id_sender = Teams_item::find($teams_item_id_to_transfer);

        //verifico che l'item esista
        if (! isset($rcd_item_id_sender->id)) {
            $this->addError('error', __('non esiste un item con l\'id selezionato'));

            return;
        }

        // NOTA BENE: l'item viene SEMPRE trasferito al team PERSONALE del destinatario

        // trovo il team PERSONALE del destinatario
        // NOTA la (where:'personal_team', '1') mi permette di trovare il team personale
        // NOTA questo team potrebbe essere stato eliminato, quindi bisogna gestire il caso
        $team_id_receiver = Team::where('user_id', $user_id_receiver)
            ->where('personal_team', '1')
            ->first(); // questo è l'id del team personale dell'utente selezionato

        // verifico di avere trovato l'id del team PERSONALE del destinatario
        if (isset($team_id_receiver)) {

            /* NOTA BENE:
            Il trasferimento dell'item ad un team esterno consiste semplicemente nel sovrascrivere l'id del team
            del mittente con l'id del team del destinatario.
             */

            // sovrascrivo il team_id dell'item sendere con il ricevitore
            $rcd_item_id_sender->team_id = $team_id_receiver->id;

            $rcd_item_id_sender->save();

            session()->flash('success', 'Trasferimento item avvenuto con successo!');

            redirect(url('/dashboard/collection/item_upload'));
            $this->closeExternalTransfer();

        } else {

            // se non esiste il team personale del destinatario, lo creo
            // il nome di defaulòt in questo caso sarà il nome dell'utente + _shopping

            $team_shopping = new Team; // creo un nuovo recordset
            $team_shopping->name = $rcd_user_receiver->name.'_shopping'; // imposto il nome del team
            $team_shopping->user_id = $user_id_receiver; // imposto l'id dell'utente
            $team_shopping->position = 0; // imposto la posizione (setto a zero affinché la nuova collection si trovi n testa alle atre)
            $team_shopping->path_image_banner = '/storage/images/default/logo_t.webp'; // imposto il path dell'immagine di default
            $team_shopping->save(); // salvo il recordset

            $rcd_item_id_sender->team_id = $team_shopping->id; // sovrascrivo il team_id dell'item sendere con il ricevitore
            $rcd_item_id_sender->save();

            redirect(url('/dashboard/collection/item_upload'));
            $this->closeExternalTransfer();

        }

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo
        di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);
    }

    public function openExternalTransfer()
    {

        // trovo l'id dell'utente destinatario tramite l'indirizzo mail
        $rcd_user_receiver = User::where('email', $this->emailExternalTransfer)->first();

        // verifico che l'utente esista
        if (isset($rcd_user_receiver->id)) {
            $this->user_id_receiver = $rcd_user_receiver->id;
        } else {
            $this->addError('error', __('label.edit_item.not_exist_user_width_this_email', ['emailExternalTransfer' => $this->emailExternalTransfer]));

            return;
        }

        // Creo il recordset del team destinatario
        $team_id_receiver = Team::where('user_id', $this->user_id_receiver)
            ->where('personal_team', '1')
            ->first(); // questo è l'id del team personale dell'utente selezionato

        if (isset($team_id_receiver)) {

            // Apro il form di conferma del trasferimento
            $this->confirmingItemTransfer = true;

            // rendo pubblico il nome del team del destinatario per poterlo usare nel messaggio di conferma
            $this->teamNameTo = $team_id_receiver->name;

        } else {
            $this->extTransfer = true;
            $this->addError('error', __('label.edit_item.personal_collection_for_this_user_not_exists'));

            return;
        }

    }

    public function closeExternalTransfer()
    {

        $this->extTransfer = false;
    }

    public function internalTransfert($itemId, $teamId, $teamName)
    {

        $transfer = Teams_item::find($itemId);

        $transfer->team_id = $teamId;
        $transfer->save();

        $this->confirmingItemTransfer = false;

        session()->flash('success', "Trasferimento dell\'item: $transfer->title alla collection $teamName avvenuto con successo!");

        return redirect(url('dashboard/collection/item_upload'));

        /*
        NOTA BENE: Invalidazione della cache.
        Questo serve a forzare il recupero dei dati anziché l'ultilizzo
        di quelli in cache...
        Quando si aggiunge uno o più file, è necessario che la vista si aggiorni per mostrare i nuovi file
        aggiunti.
         */
        $team = Auth::user()->currentTeam;
        Cache::forget('items-'.$team->id);

    }

    public function confirmItemTransfer()
    {

        $this->confirmingItemTransfer = true;

    }

    public function confirmItemInternalTransfer()
    {

        $this->confirmingItemInternalTransfer = true;

    }

    public function confirmItemRemoved($team_id)
    {
        $this->confirmItemRemoval = true;
        $this->itemIdBeingRemoved = $team_id;

    }

    public function confirmRemoveTraits($id, $key, $value)
    {

        $this->confirmingTraitsRemoved = true;
        $this->traitsIdBeingRemoved = $id;
        $this->traitKey = $key;
        $this->traitValue = $value;

    }

    public function cancelRemoveTraits()
    {

        $this->confirmingTraitsRemoved = false;
        // $this->traitsIdBeingRemoved = null;
        // $this->traitKey = null;
        // $this->traitValue = null;

    }

    public function removeTraits()
    {

        $trait = TraitsItem::find($this->traitsIdBeingRemoved);
        $trait->delete();

        $this->confirmingTraitsRemoved = false;

        $this->dispatch('saved');

    }

    public function render(): View
    {
        return view('livewire.collections.items-edit', ['itemId' => $this->itemId]);
    }
}
