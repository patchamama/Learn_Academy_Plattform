<?php
/** @var array  $course */
/** @var array  $tree   */
/** @var string $csrf   */

$courseId   = (int)$course['id'];
$courseSlug = $course['slug'];
?>
<style>
.editor-wrap {
    display: flex;
    gap: 0;
    height: calc(100vh - 120px);
    background: var(--color-bg-card, #fff);
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
}
.editor-left {
    width: 30%;
    min-width: 220px;
    max-width: 320px;
    border-right: 1px solid rgba(0,0,0,0.09);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.editor-left-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    background: rgba(0,0,0,0.02);
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.editor-tree {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem 0;
}
.editor-right {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.section-row {
    user-select: none;
}
.section-header {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.75rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--color-primary, #1e40af);
    cursor: pointer;
    border-radius: 0;
}
.section-header:hover { background: rgba(0,0,0,0.04); }
.section-header .drag-handle {
    cursor: grab;
    color: #bbb;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.section-header .toggle-icon {
    flex-shrink: 0;
    font-size: 0.7rem;
    transition: transform 0.15s;
}
.section-header.collapsed .toggle-icon { transform: rotate(-90deg); }
.section-lessons {
    padding-left: 0.5rem;
}
.section-lessons.hidden { display: none; }
.lesson-row {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem 0.35rem 1rem;
    font-size: 0.82rem;
    cursor: pointer;
    border-radius: 0;
    color: #444;
}
.lesson-row:hover { background: rgba(37,99,235,0.07); }
.lesson-row.active {
    background: rgba(37,99,235,0.12);
    color: var(--color-accent, #2563eb);
    font-weight: 600;
}
.lesson-row .drag-handle {
    cursor: grab;
    color: #ccc;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.drag-over { outline: 2px dashed var(--color-accent, #2563eb); outline-offset: -2px; }
.editor-toolbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
    background: rgba(0,0,0,0.02);
    border-bottom: 1px solid rgba(0,0,0,0.08);
    font-size: 0.8rem;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.35rem 0.75rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
}
.btn-primary { background: var(--color-accent, #2563eb); color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-success { background: #198754; color: #fff; }
.btn-outline { background: transparent; color: var(--color-accent,#2563eb); border: 1px solid var(--color-accent,#2563eb); }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.82rem; font-weight: 600; color: #555; }
.form-control {
    padding: 0.45rem 0.65rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 0.875rem;
    width: 100%;
    box-sizing: border-box;
}
.form-control:focus { outline: 2px solid var(--color-accent,#2563eb); outline-offset: 1px; border-color: transparent; }
.flash-msg {
    padding: 0.65rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
}
.flash-success { background: #d4edda; color: #155724; }
.flash-error   { background: #f8d7da; color: #721c24; }
.file-list { display: flex; flex-direction: column; gap: 0.4rem; }
.file-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.65rem;
    background: rgba(0,0,0,0.03);
    border-radius: 5px;
    font-size: 0.82rem;
}
.file-item span { flex: 1; font-family: monospace; }
.file-type-badge {
    font-size: 0.7rem;
    padding: 0.1rem 0.4rem;
    border-radius: 3px;
    background: rgba(37,99,235,0.1);
    color: var(--color-accent,#2563eb);
    font-weight: 600;
}
.panel-section { background: rgba(0,0,0,0.02); border-radius: 6px; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; }
.panel-section h3 { margin: 0; font-size: 0.875rem; font-weight: 700; color: #333; }
.placeholder-msg {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--color-text-muted);
    gap: 0.75rem;
    text-align: center;
}
</style>

<?php if (!empty($_SESSION['flash'])): ?>
<div class="flash-msg flash-<?= $_SESSION['flash']['type'] ?>" style="margin-bottom:1rem">
    <?= e($_SESSION['flash']['msg']) ?>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Top bar -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:0.75rem">
        <a href="/admin/editor" style="color:var(--color-text-muted);font-size:0.82rem;text-decoration:none">
            <i class="fa-solid fa-arrow-left"></i> All courses
        </a>
        <span style="color:#ccc">/</span>
        <h2 style="margin:0;font-size:1.05rem"><?= e($course['title']) ?></h2>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
        <form method="POST" action="/admin/editor/<?= $courseId ?>/reimport" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-secondary"
                    onclick="return confirm('Re-import will rebuild all sections and lessons from disk. Continue?')"
                    title="Re-import from disk">
                <i class="fa-solid fa-rotate"></i> Re-import from disk
            </button>
        </form>
    </div>
</div>

<!-- Main editor layout -->
<div class="editor-wrap">

    <!-- Left: tree panel -->
    <div class="editor-left">
        <div class="editor-left-header">
            <span><i class="fa-solid fa-sitemap" style="margin-right:0.3rem"></i>Structure</span>
            <button class="btn btn-primary" style="font-size:0.75rem;padding:0.25rem 0.6rem" onclick="showAddSectionModal()">
                <i class="fa-solid fa-plus"></i> Section
            </button>
        </div>

        <div class="editor-tree" id="sectionTree">
            <?php foreach ($tree as $section): ?>
            <div class="section-row"
                 data-section-id="<?= (int)$section['id'] ?>"
                 data-order="<?= (int)$section['sort_order'] ?>"
                 draggable="true">
                <div class="section-header" onclick="toggleSection(this)">
                    <span class="drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
                    <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($section['title']) ?></span>
                    <button class="btn btn-primary" style="font-size:0.65rem;padding:0.15rem 0.4rem;flex-shrink:0"
                            onclick="event.stopPropagation();showAddLessonModal(<?= (int)$section['id'] ?>)"
                            title="Add lesson">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <button class="btn btn-danger" style="font-size:0.65rem;padding:0.15rem 0.4rem;flex-shrink:0"
                            onclick="event.stopPropagation();deleteSection(<?= (int)$section['id'] ?>, '<?= e(addslashes($section['title'])) ?>')"
                            title="Delete section">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="section-lessons" data-section-id="<?= (int)$section['id'] ?>">
                    <?php foreach ($section['lessons'] as $lesson): ?>
                    <div class="lesson-row"
                         data-lesson-id="<?= (int)$lesson['id'] ?>"
                         data-section-id="<?= (int)$section['id'] ?>"
                         data-order="<?= (int)$lesson['sort_order'] ?>"
                         draggable="true"
                         onclick="selectLesson(<?= (int)$lesson['id'] ?>)">
                        <span class="drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
                        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <span style="color:#aaa;font-size:0.75rem;margin-right:0.3rem"><?= e($lesson['prefix']) ?></span>
                            <?= e($lesson['title']) ?>
                        </span>
                        <button class="btn btn-danger" style="font-size:0.6rem;padding:0.1rem 0.35rem;flex-shrink:0"
                                onclick="event.stopPropagation();deleteLesson(<?= (int)$lesson['id'] ?>, '<?= e(addslashes($lesson['title'])) ?>')"
                                title="Delete lesson">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: lesson config panel -->
    <div class="editor-right" id="editorRight">
        <div class="placeholder-msg" id="editorPlaceholder">
            <i class="fa-solid fa-hand-pointer" style="font-size:2.5rem;color:#ddd"></i>
            <p style="margin:0;font-size:0.9rem">Select a lesson on the left to edit its configuration.</p>
        </div>

        <div id="lessonPanel" style="display:none;flex-direction:column;gap:1.25rem">

            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-size:0.75rem;color:var(--color-text-muted)" id="lessonBreadcrumb"></div>
                    <div style="font-weight:700;font-size:1rem" id="lessonTitle">—</div>
                </div>
                <a id="previewLink" href="#" target="_blank" class="btn btn-outline">
                    <i class="fa-solid fa-eye"></i> Preview
                </a>
            </div>

            <!-- Config form -->
            <div class="panel-section">
                <h3><i class="fa-solid fa-sliders" style="margin-right:0.4rem"></i>Configuration</h3>
                <form method="POST" id="configForm" action="" style="display:flex;flex-direction:column;gap:0.75rem">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

                    <div class="form-group">
                        <label for="cfg_title">Title</label>
                        <input type="text" name="title" id="cfg_title" class="form-control" placeholder="Lesson title">
                    </div>

                    <div class="form-group">
                        <label for="cfg_layout">Layout</label>
                        <select name="layout" id="cfg_layout" class="form-control">
                            <option value="default">Default</option>
                            <option value="video-first">Video first</option>
                            <option value="text-first">Text first</option>
                            <option value="audio-only">Audio only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cfg_description">Description</label>
                        <textarea name="description" id="cfg_description" class="form-control" rows="3" placeholder="Short lesson description"></textarea>
                    </div>

                    <div style="display:flex;gap:1.5rem">
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.875rem;cursor:pointer">
                            <input type="checkbox" name="show_attachments" id="cfg_show_attachments">
                            Show attachments
                        </label>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.875rem;cursor:pointer">
                            <input type="checkbox" name="show_image_gallery" id="cfg_show_image_gallery">
                            Show image gallery
                        </label>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save config
                        </button>
                    </div>
                </form>
            </div>

            <!-- Files -->
            <div class="panel-section">
                <h3><i class="fa-solid fa-paperclip" style="margin-right:0.4rem"></i>Files</h3>
                <div class="file-list" id="fileList">
                    <p style="color:var(--color-text-muted);font-size:0.82rem;margin:0">No files attached.</p>
                </div>
            </div>

            <!-- Upload -->
            <div class="panel-section">
                <h3><i class="fa-solid fa-upload" style="margin-right:0.4rem"></i>Upload file</h3>
                <form method="POST" id="uploadForm" action="" enctype="multipart/form-data"
                      style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="file" name="file" class="form-control" style="max-width:320px"
                           accept=".mp4,.webm,.mov,.mp3,.wav,.ogg,.m4a,.jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.vtt,.srt,.zip,.docx,.md,.txt,.html">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Add Section modal -->
<div id="modalSection" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:1.5rem;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.18)">
        <h3 style="margin:0 0 1rem">Add Section</h3>
        <form method="POST" action="/admin/editor/<?= $courseId ?>/section" style="display:flex;flex-direction:column;gap:0.75rem">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="form-group">
                <label>Section title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Introduction" required>
            </div>
            <div class="form-group">
                <label>Folder name</label>
                <input type="text" name="folder_name" class="form-control" placeholder="e.g. 01-introduction" required>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:0.5rem">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalSection')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Section</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Lesson modal -->
<div id="modalLesson" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:1.5rem;max-width:420px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.18)">
        <h3 style="margin:0 0 1rem">Add Lesson</h3>
        <form method="POST" id="addLessonForm" action="" style="display:flex;flex-direction:column;gap:0.75rem">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="form-group">
                <label>Lesson title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Getting Started" required>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:0.5rem">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalLesson')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Lesson</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete helpers (hidden forms) -->
<form method="POST" id="deleteSectionForm" action="" style="display:none">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
</form>
<form method="POST" id="deleteLessonForm" action="" style="display:none">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
</form>

<script>
// ── Course data encoded for client-side switching ──────────────────────────
const COURSE_ID   = <?= (int)$courseId ?>;
const COURSE_SLUG = <?= json_encode($courseSlug) ?>;
const CSRF_TOKEN  = <?= json_encode($csrf) ?>;

const LESSONS_DATA = <?php
    $allLessons = [];
    foreach ($tree as $s) {
        foreach ($s['lessons'] as $l) {
            $allLessons[] = [
                'id'            => $l['id'],
                'section_id'    => $s['id'],
                'section_title' => $s['title'],
                'title'         => $l['title'],
                'prefix'        => $l['prefix'],
                'config'        => $l['config'],
                'files'         => $l['files'],
            ];
        }
    }
    echo json_encode($allLessons);
?>;

// index by id
const lessonMap = {};
LESSONS_DATA.forEach(l => lessonMap[l.id] = l);

// ── Lesson panel switching ─────────────────────────────────────────────────
function selectLesson(lessonId) {
    // Highlight active lesson row
    document.querySelectorAll('.lesson-row').forEach(el => el.classList.remove('active'));
    const row = document.querySelector('.lesson-row[data-lesson-id="' + lessonId + '"]');
    if (row) row.classList.add('active');

    const l = lessonMap[lessonId];
    if (!l) return;

    // Show panel
    document.getElementById('editorPlaceholder').style.display = 'none';
    const panel = document.getElementById('lessonPanel');
    panel.style.display = 'flex';

    // Breadcrumb + title
    document.getElementById('lessonBreadcrumb').textContent = l.section_title;
    document.getElementById('lessonTitle').textContent = l.prefix + ' — ' + l.title;

    // Preview link
    document.getElementById('previewLink').href = '/courses/' + encodeURIComponent(COURSE_SLUG) + '/lesson/' + lessonId;

    // Config form
    document.getElementById('configForm').action = '/admin/editor/' + COURSE_ID + '/lesson/' + lessonId;
    document.getElementById('cfg_title').value            = l.config.title        || '';
    document.getElementById('cfg_layout').value           = l.config.layout       || 'default';
    document.getElementById('cfg_description').value      = l.config.description  || '';
    document.getElementById('cfg_show_attachments').checked   = l.config.show_attachments   !== false;
    document.getElementById('cfg_show_image_gallery').checked = l.config.show_image_gallery !== false;

    // Upload form
    document.getElementById('uploadForm').action = '/admin/editor/' + COURSE_ID + '/lesson/' + lessonId + '/upload';

    // Files list
    renderFileList(l.files, lessonId);
}

function renderFileList(files, lessonId) {
    const container = document.getElementById('fileList');
    if (!files || files.length === 0) {
        container.innerHTML = '<p style="color:var(--color-text-muted);font-size:0.82rem;margin:0">No files attached.</p>';
        return;
    }
    container.innerHTML = files.map(f => `
        <div class="file-item">
            <i class="fa-solid ${fileIcon(f.file_type)}" style="color:var(--color-accent);flex-shrink:0"></i>
            <span title="${escHtml(f.path)}">${escHtml(f.filename)}</span>
            <span class="file-type-badge">${escHtml(f.file_type)}</span>
            <form method="POST" action="/admin/editor/${COURSE_ID}/file/${f.id}/delete" style="display:inline;margin-left:auto"
                  onsubmit="return confirm('Delete ${escHtml(f.filename)}?')">
                <input type="hidden" name="_csrf" value="${escHtml(CSRF_TOKEN)}">
                <button type="submit" class="btn btn-danger" style="font-size:0.7rem;padding:0.15rem 0.4rem">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
    `).join('');
}

function fileIcon(type) {
    const icons = {video:'fa-film', audio:'fa-music', image:'fa-image', markdown:'fa-file-code',
                   html:'fa-file-code', text:'fa-file-lines', subtitle:'fa-closed-captioning', attachment:'fa-paperclip'};
    return icons[type] || 'fa-file';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Section collapse ───────────────────────────────────────────────────────
function toggleSection(headerEl) {
    const sectionId = headerEl.closest('.section-row').dataset.sectionId;
    const lessonsEl = document.querySelector('.section-lessons[data-section-id="' + sectionId + '"]');
    if (!lessonsEl) return;
    lessonsEl.classList.toggle('hidden');
    headerEl.classList.toggle('collapsed');
}

// ── Modals ─────────────────────────────────────────────────────────────────
function showAddSectionModal() {
    document.getElementById('modalSection').style.display = 'flex';
}
function showAddLessonModal(sectionId) {
    document.getElementById('addLessonForm').action = '/admin/editor/' + COURSE_ID + '/section/' + sectionId + '/lesson';
    document.getElementById('modalLesson').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
// Close modals on backdrop click
['modalSection','modalLesson'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// ── Delete helpers ─────────────────────────────────────────────────────────
function deleteSection(sectionId, title) {
    if (!confirm('Delete section "' + title + '" and ALL its lessons? This cannot be undone.')) return;
    const form = document.getElementById('deleteSectionForm');
    form.action = '/admin/editor/' + COURSE_ID + '/section/' + sectionId + '/delete';
    form.submit();
}
function deleteLesson(lessonId, title) {
    if (!confirm('Delete lesson "' + title + '"? This cannot be undone.')) return;
    const form = document.getElementById('deleteLessonForm');
    form.action = '/admin/editor/' + COURSE_ID + '/lesson/' + lessonId + '/delete';
    form.submit();
}

// ── Drag-and-drop reorder ──────────────────────────────────────────────────
let dragSrc = null;
let dragType = null; // 'section' | 'lesson'

function attachDragHandlers() {
    // Sections
    document.querySelectorAll('.section-row').forEach(el => {
        el.addEventListener('dragstart', e => {
            if (!e.target.classList.contains('section-row') && !e.target.closest('.drag-handle')?.closest('.section-header')) {
                // Only initiate drag from the handle within section-header
                if (!e.target.closest('.section-header .drag-handle')) { e.preventDefault(); return; }
            }
            dragSrc  = el;
            dragType = 'section';
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(() => el.style.opacity = '0.5', 0);
        });
        el.addEventListener('dragend', () => {
            el.style.opacity = '';
            document.querySelectorAll('.drag-over').forEach(x => x.classList.remove('drag-over'));
            saveReorder();
        });
        el.addEventListener('dragover', e => {
            if (dragType !== 'section') return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            document.querySelectorAll('.section-row.drag-over').forEach(x => x.classList.remove('drag-over'));
            el.classList.add('drag-over');
        });
        el.addEventListener('drop', e => {
            if (dragType !== 'section' || dragSrc === el) return;
            e.preventDefault();
            const tree = document.getElementById('sectionTree');
            const els  = [...tree.querySelectorAll(':scope > .section-row')];
            const srcI = els.indexOf(dragSrc);
            const dstI = els.indexOf(el);
            if (srcI < dstI) el.after(dragSrc); else el.before(dragSrc);
        });
    });

    // Lessons
    document.querySelectorAll('.lesson-row').forEach(el => {
        el.addEventListener('dragstart', e => {
            if (!e.target.closest('.lesson-row .drag-handle')) { e.preventDefault(); return; }
            dragSrc  = el;
            dragType = 'lesson';
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(() => el.style.opacity = '0.5', 0);
        });
        el.addEventListener('dragend', () => {
            el.style.opacity = '';
            document.querySelectorAll('.drag-over').forEach(x => x.classList.remove('drag-over'));
            saveReorder();
        });
        el.addEventListener('dragover', e => {
            if (dragType !== 'lesson') return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            document.querySelectorAll('.lesson-row.drag-over').forEach(x => x.classList.remove('drag-over'));
            el.classList.add('drag-over');
        });
        el.addEventListener('drop', e => {
            if (dragType !== 'lesson' || dragSrc === el) return;
            e.preventDefault();
            const container    = el.closest('.section-lessons');
            const srcContainer = dragSrc.closest('.section-lessons');

            if (container !== srcContainer) {
                // Cross-section move: insert before target in new section
                el.before(dragSrc);
                dragSrc.dataset.sectionId = container.dataset.sectionId;
            } else {
                const els  = [...container.querySelectorAll(':scope > .lesson-row')];
                const srcI = els.indexOf(dragSrc);
                const dstI = els.indexOf(el);
                if (srcI < dstI) el.after(dragSrc); else el.before(dragSrc);
            }
        });
    });
}

function saveReorder() {
    const sections = [];
    document.querySelectorAll('#sectionTree > .section-row').forEach((el, idx) => {
        sections.push({ id: parseInt(el.dataset.sectionId), order: idx });
    });

    const lessons = [];
    document.querySelectorAll('.section-lessons').forEach(container => {
        const sectionId = parseInt(container.dataset.sectionId);
        container.querySelectorAll(':scope > .lesson-row').forEach((el, idx) => {
            lessons.push({ id: parseInt(el.dataset.lessonId), order: idx, sectionId });
        });
    });

    fetch('/admin/editor/' + COURSE_ID + '/reorder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ _csrf: CSRF_TOKEN, sections, lessons }),
    }).catch(() => {/* silently ignore network errors */});
}

// Init drag handlers
attachDragHandlers();
</script>
