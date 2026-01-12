<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isFirmaSahibi()) {
    header("Location: login.php");
    exit;
}
header('Content-Type: text/html; charset=utf-8');

$db = getDB();
$firma_id = $_SESSION['firma_id'];
$id = (int) ($_GET['id'] ?? 0);

// Şablonu getir
$stmt = $db->prepare("SELECT * FROM sozlesme_sablonlari WHERE id = :id AND firma_id = :firma_id");
$stmt->execute([':id' => $id, ':firma_id' => $firma_id]);
$sablon = $stmt->fetch();

if (!$sablon) {
    die("Şablon bulunamadı!");
}

// Dosya uzantısını kontrol et (Sadece resimler düzenlenebilir)
$ext = strtolower(pathinfo($sablon['dosya_yolu'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
    die("Bu düzenleyici sadece resim dosyaları (JPG, PNG) için kullanılabilir. PDF desteği ileride eklenecektir.");
}

// Kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sahalar = $_POST['sahalar'] ?? '[]';

    $update = $db->prepare("UPDATE sozlesme_sablonlari SET sahalar = :sahalar WHERE id = :id");
    $update->execute([':sahalar' => $sahalar, ':id' => $id]);

    header("Location: sablon-duzenle.php?id=$id&saved=1");
    exit;
}

$mevcut_sahalar = $sablon['sahalar'] ? $sablon['sahalar'] : '[]';

$page_title = 'Şablon Düzenle: ' . $sablon['ad'];
$body_class = 'firma-theme'; // Allow theme to handle base styles
$extra_css = '
<style>
    /* Editor specific styles overrides */
    /* Remove padding override if theme handles it, but keep editor container layout */
    
    .editor-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        height: calc(100vh - 150px); /* Adjusted for layout header/padding */
    }

    .toolbox {
        width: 300px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        flex-shrink: 0;
    }

    .canvas-area {
        flex-grow: 1;
        background: #e9ecef;
        border-radius: 10px;
        position: relative;
        overflow: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 20px;
    }

    .canvas-wrapper {
        position: relative;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        background: white;
    }

    .canvas-wrapper img {
        max-width: 100%;
        display: block;
        pointer-events: none;
    }

    .field-item {
        padding: 10px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        cursor: move;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
    }

    .field-item:hover {
        background: #e2e6ea;
        border-color: #adb5bd;
    }

    .field-item i {
        color: #667eea;
    }

    .placed-field {
        position: absolute;
        background: rgba(102, 126, 234, 0.2);
        border: 2px solid #667eea;
        color: #1a1a1a;
        padding: 2px 5px;
        cursor: grab;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        box-sizing: border-box;
        user-select: none;
        /* Default alignment */
        text-align: left;
    }

    .placed-field:active {
        cursor: grabbing;
    }

    .placed-field .label-text {
        width: 100%;
        pointer-events: none;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none; /* Let clicks pass to parent */
    }

    .placed-field.selected {
        border: 2px solid #dc3545 !important; /* Red border */
        background: rgba(220, 53, 69, 0.2) !important; /* Reddish background */
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.5); /* Glow effect */
        z-index: 1000 !important;
    }

    /* Controls: remove btn */
    .placed-field .remove-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: red;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 10px;
        z-index: 10;
    }

    /* Controls: alignment toolbar */
    .placed-field .align-toolbar {
        position: absolute;
        bottom: -25px;
        /* Altına taşı */
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        border-radius: 4px;
        display: none;
        padding: 2px;
        z-index: 10;
        gap: 2px;
        white-space: nowrap;
    }

    .placed-field:hover .remove-btn,
    .placed-field:hover .align-toolbar {
        display: flex;
    }

    /* Eğer taşınıyorsa gizle */
    .placed-field.dragging .align-toolbar,
    .placed-field.dragging .remove-btn {
        display: none !important;
    }

    .align-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 10px;
        padding: 2px 4px;
        cursor: pointer;
        border-radius: 2px;
    }

    .align-btn:hover {
        background: #555;
    }

    .align-btn.active {
        background: #667eea;
    }

    .resize-handle {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        background: #667eea;
        cursor: se-resize;
    }

    .category-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #6c757d;
        margin-top: 15px;
        margin-bottom: 10px;
        font-weight: bold;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 5px;
    }
</style>
';

// Sidebar count retrieval (needed if sidebar uses it, which it does but sidebar.php logic usually handles it if undefined,
// but for consistency let's define it if possible, or let sidebar handle it. 
// In sidebar.php: if(isset($sablon_count))...
// We can calculate it here to show the badge.
$sablon_count_sql = "SELECT COUNT(*) as sayi FROM sozlesme_sablonlari WHERE firma_id = :firma_id AND aktif = 1";
$sablon_stmt = $db->prepare($sablon_count_sql);
$sablon_stmt->execute([':firma_id' => $firma_id]);
$sablon_count = $sablon_stmt->fetch()['sayi'];

?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center pt-3">
            <div>
                <h4 class="mb-0">Şablon Düzenleyici: <?php echo htmlspecialchars($sablon['ad']); ?></h4>
                <small class="text-muted">Alanları sürükleyip resmin üzerine bırakın. Hizalama için alanın üzerine
                    gelin.</small>
            </div>
            <div>
                <a href="sablonlar.php" class="btn btn-outline-secondary me-2">İptal</a>
                <button onclick="saveDesign()" class="btn btn-primary">
                    <i class="bi bi-save"></i> Kaydet
                </button>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success mt-3 py-2">
                <i class="bi bi-check-circle"></i> Değişiklikler kaydedildi.
            </div>
        <?php endif; ?>

        <div class="editor-container">
            <!-- Araç Kutusu -->
            <div class="toolbox">
                <!-- Properties Panel (Initially Hidden) -->
                <div id="propertiesPanel" class="card mb-3 border-primary" style="display:none; background: #f8f9fa;">
                    <div
                        class="card-header bg-primary text-white py-1 px-2 d-flex justify-content-between align-items-center">
                        <small class="fw-bold">Seçili Alan</small>
                        <button onclick="deleteSelected()" class="btn btn-sm btn-danger py-0 px-2"
                            title="Sil">×</button>
                    </div>
                    <div class="card-body p-2">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Yazı Boyutu: <span
                                    id="val-fontsize">12</span>px</label>
                            <div class="input-group input-group-sm">
                                <button class="btn btn-outline-secondary"
                                    onclick="updateProp('fontSize', -1)">-</button>
                                <button class="btn btn-outline-secondary" onclick="updateProp('fontSize', 1)">+</button>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small mb-1">Hizalama</label>
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="updateProp('align', 'L')" id="btn-align-L"><i
                                        class="bi bi-text-left"></i></button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="updateProp('align', 'C')" id="btn-align-C"><i
                                        class="bi bi-text-center"></i></button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="updateProp('align', 'R')" id="btn-align-R"><i
                                        class="bi bi-text-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="category-title mt-0">Firma Bilgileri</div>
                <div class="field-item" draggable="true" data-type="firma_logo" data-label="Firma Logosu">
                    <i class="bi bi-image"></i> Firma Logosu
                </div>
                <div class="field-item" draggable="true" data-type="ticari_unvan" data-label="Ticari Ünvan">
                    <i class="bi bi-building"></i> Ticari Ünvan
                </div>
                <div class="field-item" draggable="true" data-type="firma_adres" data-label="Firma Adresi">
                    <i class="bi bi-geo-alt"></i> Adres
                </div>
                <div class="field-item" draggable="true" data-type="firma_telefon" data-label="Firma Telefon">
                    <i class="bi bi-telephone"></i> Telefon
                </div>
                <div class="field-item" draggable="true" data-type="yetki_belge_no" data-label="Yetki Belge No">
                    <i class="bi bi-award"></i> Yetki Belge No
                </div>

                <div class="category-title">Danışman Bilgileri</div>
                <div class="field-item" draggable="true" data-type="danisman_ad" data-label="Danışman Adı">
                    <i class="bi bi-person-badge"></i> Ad Soyad
                </div>
                <div class="field-item" draggable="true" data-type="danisman_telefon" data-label="Danışman Tel">
                    <i class="bi bi-phone"></i> Telefon
                </div>

                <div class="category-title">Gayrimenkul Bilgileri</div>
                <div class="field-item" draggable="true" data-type="il" data-label="İl">
                    <i class="bi bi-map"></i> İl
                </div>
                <div class="field-item" draggable="true" data-type="ilce" data-label="İlçe">
                    <i class="bi bi-pin-map"></i> İlçe
                </div>
                <div class="field-item" draggable="true" data-type="mahalle" data-label="Mahalle">
                    <i class="bi bi-geo"></i> Mahalle
                </div>
                <div class="field-item" draggable="true" data-type="ada" data-label="Ada">
                    <i class="bi bi-grid-3x3"></i> Ada
                </div>
                <div class="field-item" draggable="true" data-type="parsel" data-label="Parsel">
                    <i class="bi bi-grid"></i> Parsel
                </div>
                <div class="field-item" draggable="true" data-type="bagimsiz_bolum" data-label="Bağ. Bölüm No">
                    <i class="bi bi-door-open"></i> Bağ. Bölüm No
                </div>
                <div class="field-item" draggable="true" data-type="nitelik" data-label="Niteliği">
                    <i class="bi bi-house"></i> Niteliği
                </div>
                <div class="field-item" draggable="true" data-type="tam_adres" data-label="Açık Adres">
                    <i class="bi bi-signpost-2"></i> Açık Adres
                </div>
                <div class="field-item" draggable="true" data-type="fiyat" data-label="Fiyat">
                    <i class="bi bi-currency-try"></i> Fiyat
                </div>
                <div class="field-item" draggable="true" data-type="hizmet_bedeli" data-label="Hizmet Bedeli">
                    <i class="bi bi-cash-stack"></i> Hizmet Bedeli
                </div>

                <div class="category-title">Müşteri & İmza</div>
                <div class="field-item" draggable="true" data-type="musteri_ad" data-label="Müşteri Adı">
                    <i class="bi bi-person"></i> Müşteri Adı
                </div>
                <div class="field-item" draggable="true" data-type="musteri_tc" data-label="TC Kimlik">
                    <i class="bi bi-card-heading"></i> TC Kimlik
                </div>
                <div class="field-item" draggable="true" data-type="musteri_telefon" data-label="Müşteri Telefonu">
                    <i class="bi bi-phone"></i> Müşteri Telefonu
                </div>
                <div class="field-item" draggable="true" data-type="musteri_adres" data-label="Müşteri Adresi">
                    <i class="bi bi-geo-alt"></i> Müşteri Adresi
                </div>
                <div class="field-item" draggable="true" data-type="imza_yer_gosterme" data-label="Yer Gösterme İmzası">
                    <i class="bi bi-pen"></i> İmza (Yer Gösterme)
                </div>
                <div class="field-item" draggable="true" data-type="imza_teyit" data-label="Teyit İmzası">
                    <i class="bi bi-pen-fill"></i> İmza (Teyit)
                </div>

                <div class="category-title">Diğer</div>
                <div class="field-item" draggable="true" data-type="tarih" data-label="Tarih" title="Bugünün Tarihi">
                    <i class="bi bi-calendar-date"></i> Tarih (Otomatik)
                </div>
            </div>

            <!-- Canvas -->
            <div class="canvas-area" id="canvasArea">
                <div class="canvas-wrapper" id="canvasWrapper">
                    <img src="../<?php echo htmlspecialchars($sablon['dosya_yolu']); ?>" id="templateImage">
                    <!-- Alanlar buraya eklenecek -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Saving -->
<form id="saveForm" method="POST" style="display:none;">
    <input type="hidden" name="sahalar" id="sahalarInput">
</form>

<?php
// JavaScript Logic
$extra_js = '
<script>
    // Veriler PHP\'den geliyor
    const savedFields = ' . json_encode(json_decode($mevcut_sahalar), JSON_UNESCAPED_UNICODE) . ';
    const canvasWrapper = document.getElementById(\'canvasWrapper\');
    const templateImage = document.getElementById(\'templateImage\');
    const propertiesPanel = document.getElementById(\'propertiesPanel\');
    
    let selectedElement = null;

    // Canvas dışına tıklayınca seçimi kaldır
    document.addEventListener(\'click\', (e) => {
        if (!e.target.closest(\'.placed-field\') && !e.target.closest(\'#propertiesPanel\') && !e.target.classList.contains(\'resize-handle\')) {
            deselectAll();
        }
    });

    // Sürükle Bırak İşlemleri
    document.querySelectorAll(\'.field-item\').forEach(item => {
        item.addEventListener(\'dragstart\', (e) => {
            e.dataTransfer.setData(\'type\', item.dataset.type);
            e.dataTransfer.setData(\'label\', item.dataset.label);
        });
    });

    canvasWrapper.addEventListener(\'dragover\', (e) => e.preventDefault());

    canvasWrapper.addEventListener(\'drop\', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData(\'type\');
        const label = e.dataTransfer.getData(\'label\');

        // Mouse pozisyonunu hesapla (resme göre)
        const rect = canvasWrapper.getBoundingClientRect();
        const x = e.clientX - rect.left - 75; // Ortalama için
        const y = e.clientY - rect.top - 15;

        addFieldToCanvas(type, label, x, y);
    });

    // Alan Ekleme Fonksiyonu
    function addFieldToCanvas(type, label, x, y, width = 150, height = 30, align = \'L\', fontSize = 12) {
        const el = document.createElement(\'div\');
        el.className = \'placed-field\';
        el.dataset.type = type;
        el.dataset.align = align; 
        el.dataset.fontSize = fontSize; 

        el.style.left = x + \'px\';
        el.style.top = y + \'px\';
        el.style.width = width + \'px\';
        el.style.height = height + \'px\';
        el.style.textAlign = align === \'L\' ? \'left\' : (align === \'C\' ? \'center\' : \'right\');
        el.style.fontSize = fontSize + \'px\';
        el.style.lineHeight = height + \'px\'; // Center vertically roughly

        // İçerik (Label)
        const span = document.createElement(\'span\');
        span.className = \'label-text\';
        span.textContent = label;
        el.appendChild(span);

        // Eğer imza alanıysa
        if (type.includes(\'imza\')) {
            el.style.height = \'60px\';
            el.style.width = \'100px\';
            el.style.background = \'rgba(255, 193, 7, 0.2)\';
            el.style.borderColor = \'#ffc107\';
        }
        // Firma Logo ise
        else if (type === \'firma_logo\') {
            el.style.height = \'60px\';
            el.style.width = \'60px\';
            el.style.background = \'rgba(40, 167, 69, 0.2)\';
            el.style.borderColor = \'#28a745\';
            span.style.display = \'none\'; // Logoda metin gizle, sadece kutu
            el.setAttribute(\'title\', \'Firma Logosu Yeri\');
        }

        // Resize handle
        const resizer = document.createElement(\'div\');
        resizer.className = \'resize-handle\';
        el.appendChild(resizer);

        // Seçim Mantığı
        el.addEventListener(\'mousedown\', (e) => {
             if (e.target.classList.contains(\'resize-handle\')) return; 
             selectField(el);
        });

        // Sürükleme Logic
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        el.addEventListener(\'mousedown\', (e) => {
            if (e.target.classList.contains(\'resize-handle\')) return;

            isDragging = true;
            el.classList.add(\'dragging\');
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = el.offsetLeft;
            initialTop = el.offsetTop;
            el.style.zIndex = 1000;
        });

        // Resize Logic
        let isResizing = false;
        let startW, startH;

        resizer.addEventListener(\'mousedown\', (e) => {
            isResizing = true;
            e.stopPropagation();
            startX = e.clientX;
            startY = e.clientY;
            startW = el.offsetWidth;
            startH = el.offsetHeight;
        });

        window.addEventListener(\'mousemove\', (e) => {
            if (isDragging) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                el.style.left = (initialLeft + dx) + \'px\';
                el.style.top = (initialTop + dy) + \'px\';
            }
            if (isResizing) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                el.style.width = (startW + dx) + \'px\';
                el.style.height = (startH + dy) + \'px\';
                el.style.lineHeight = (startH + dy) + \'px\'; // Update line height on resize
            }
        });

        window.addEventListener(\'mouseup\', () => {
            isDragging = false;
            isResizing = false;
            el.classList.remove(\'dragging\');
            el.style.zIndex = el.classList.contains(\'selected\') ? 100 : \'\';
        });

        canvasWrapper.appendChild(el);
        // Yeni eklenen elemanı seç
        selectField(el);
    }

    function selectField(el) {
        deselectAll();
        selectedElement = el;
        el.classList.add(\'selected\');
        
        // Update Panel
        if (el.dataset.type !== \'firma_logo\') {
             propertiesPanel.style.display = \'block\';
             
             // Update Values
             const currentSize = parseInt(el.dataset.fontSize) || 12;
             document.getElementById(\'val-fontsize\').innerText = currentSize;
             
             // Update Align Buttons
             const currentAlign = el.dataset.align || \'L\';
             document.querySelectorAll(\'#propertiesPanel .btn-group button\').forEach(b => {
                b.classList.remove(\'active\', \'btn-secondary\');
                b.classList.add(\'btn-outline-secondary\');
             });
             
             const activeBtn = document.getElementById(\'btn-align-\' + currentAlign);
             if(activeBtn) {
                 activeBtn.classList.remove(\'btn-outline-secondary\');
                 activeBtn.classList.add(\'btn-secondary\', \'active\');
             }
        } else {
             propertiesPanel.style.display = \'none\';
        }
    }

    function deselectAll() {
        selectedElement = null;
        document.querySelectorAll(\'.placed-field\').forEach(el => el.classList.remove(\'selected\'));
        if(propertiesPanel) propertiesPanel.style.display = \'none\';
    }

    function deleteSelected() {
        if (selectedElement) {
            selectedElement.remove();
            deselectAll();
        }
    }

    function updateProp(prop, val) {
        if (!selectedElement) return;

        if (prop === \'fontSize\') {
            let current = parseInt(selectedElement.dataset.fontSize) || 12;
            current += val;
            if (current < 8) current = 8;
            if (current > 72) current = 72;
            
            selectedElement.dataset.fontSize = current;
            selectedElement.style.fontSize = current + \'px\';
            document.getElementById(\'val-fontsize\').innerText = current;
        } else if (prop === \'align\') {
            selectedElement.dataset.align = val;
            selectedElement.style.textAlign = val === \'L\' ? \'left\' : (val === \'C\' ? \'center\' : \'right\');
            
            // Toggle buttons
             document.querySelectorAll(\'#propertiesPanel .btn-group button\').forEach(b => {
                b.classList.remove(\'active\', \'btn-secondary\');
                b.classList.add(\'btn-outline-secondary\');
             });
             
             const activeBtn = document.getElementById(\'btn-align-\' + val);
             if(activeBtn) {
                 activeBtn.classList.remove(\'btn-outline-secondary\');
                 activeBtn.classList.add(\'btn-secondary\', \'active\');
             }
        }
    }

    // Mevcut alanları yükle
    templateImage.onload = () => {
        savedFields.forEach(field => {
            const realW = templateImage.naturalWidth;
            const realH = templateImage.naturalHeight;
            const displayW = templateImage.width;
            const displayH = templateImage.height;

            const ratioX = displayW / realW;
            const ratioY = displayH / realH;

            addFieldToCanvas(
                field.type,
                field.label,
                field.x * ratioX,
                field.y * ratioY,
                field.w * ratioX,
                field.h * ratioY,
                field.align || \'L\',
                field.fontSize || 12
            );
        });
    };

    if (templateImage.complete) templateImage.onload();

    function saveDesign() {
        const fields = [];

        const realW = templateImage.naturalWidth;
        const realH = templateImage.naturalHeight;
        const displayW = templateImage.width;
        const displayH = templateImage.height;

        const ratioX = realW / displayW;
        const ratioY = realH / displayH;

        document.querySelectorAll(\'.placed-field\').forEach(el => {
            fields.push({
                type: el.dataset.type,
                label: el.querySelector(\'.label-text\').textContent,
                x: Math.round(el.offsetLeft * ratioX),
                y: Math.round(el.offsetTop * ratioY),
                w: Math.round(el.offsetWidth * ratioX),
                h: Math.round(el.offsetHeight * ratioY),
                align: el.dataset.align,
                fontSize: parseInt(el.dataset.fontSize) || 12
            });
        });

        document.getElementById(\'sahalarInput\').value = JSON.stringify(fields);
        document.getElementById(\'saveForm\').submit();
    }
</script>
';
include '../includes/footer.php';
?>