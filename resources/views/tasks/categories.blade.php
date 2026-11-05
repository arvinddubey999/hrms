@extends('layouts.app')

@section('content')
<div class="row" style="justify-content:space-between;align-items:center;margin-bottom:18px">
    <div>
        <h1 style="margin:0;font-size:22px;display:flex;align-items:center;gap:10px">
            <i class="fa-solid fa-tags" style="color:var(--accent)"></i> Task Categories
        </h1>
        <p class="muted" style="margin:4px 0 0;font-size:12.5px">
            Manage custom task categories for work assignment, tracking, and organization.
        </p>
    </div>

    <div style="display:flex;align-items:center;gap:10px">
        <a href="{{ route('tasks.index') }}" class="btn light" style="font-size:12.5px;padding:8px 14px;border-radius:8px">
            <i class="fa-solid fa-arrow-left"></i> Back to Tasks
        </a>
        <button type="button" class="btn" onclick="openAddCategoryModal()" style="background:#1e293b;color:#fff;border-radius:8px;padding:8px 16px;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:6px">
            <i class="fa-solid fa-plus"></i> Add New Category
        </button>
    </div>
</div>

<!-- STAT SUMMARY CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:20px">
    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#e0e7ff;color:#4338ca;display:grid;place-items:center;font-size:20px">
            <i class="fa-solid fa-tags"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Total Categories</div>
            <div style="font-size:22px;font-weight:800;color:#0f172a">{{ $totalCategories }}</div>
        </div>
    </div>

    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-size:20px">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Created by You</div>
            <div style="font-size:22px;font-weight:800;color:#16a34a">{{ $myCategoriesCount }}</div>
        </div>
    </div>

    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#fef3c7;color:#b45309;display:grid;place-items:center;font-size:20px">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Total Tagged Tasks</div>
            <div style="font-size:22px;font-weight:800;color:#b45309">{{ $totalTaggedTasks }}</div>
        </div>
    </div>
</div>

<!-- CATEGORIES TABLE CARD -->
<div class="card" style="padding:0;overflow:hidden">
    <!-- Search / Filter bar -->
    <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:#fafafa">
        <div style="position:relative;flex:1;max-width:360px">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px"></i>
            <input type="text" id="catSearchInput" oninput="filterCategoriesTable()" placeholder="Search category by name or creator..." style="width:100%;padding:7px 12px 7px 32px;font-size:12.5px;border:1px solid #cbd5e1;border-radius:8px;outline:none;background:#fff">
        </div>
        <div class="muted" style="font-size:12px">
            Showing <strong id="visibleCategoryCount">{{ $categories->count() }}</strong> of {{ $categories->count() }} categories
        </div>
    </div>

    <div style="overflow-x:auto">
        <table class="table" style="width:100%;border-collapse:collapse;font-size:13px" id="categoryListingTable">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:left;color:#475569;font-size:11.5px;text-transform:uppercase;letter-spacing:0.5px">
                    <th style="padding:12px 16px;width:50px;text-align:center">#</th>
                    <th style="padding:12px 16px">Category Name</th>
                    <th style="padding:12px 16px">Created By</th>
                    <th style="padding:12px 16px;text-align:center">Tagged Tasks</th>
                    <th style="padding:12px 16px">Created Date</th>
                    <th style="padding:12px 16px;text-align:right;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    @php
                        $canManage = $u->isAdmin() || $u->isSuperAdmin() || ($cat->created_by && $cat->created_by == $u->id);
                        $creatorName = $cat->creator ? $cat->creator->displayName() : 'System Default';
                    @endphp
                    <tr class="cat-row" data-name="{{ strtolower($cat->name) }}" data-creator="{{ strtolower($creatorName) }}" style="border-bottom:1px solid #f1f5f9;transition:background 0.15s ease" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 16px;text-align:center;color:#64748b;font-weight:600">{{ $loop->iteration }}</td>
                        <td style="padding:12px 16px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="chip" style="background:#e0e7ff;color:#4338ca;font-weight:700;font-size:12px;padding:3px 10px;border-radius:12px;border:1px solid #c7d2fe">
                                    <i class="fa-solid fa-tag" style="font-size:10px"></i> {{ $cat->name }}
                                </span>
                            </div>
                        </td>
                        <td style="padding:12px 16px;color:#334155">
                            @if($cat->creator)
                                <div style="display:inline-flex;align-items:center;gap:6px">
                                    <i class="fa-solid fa-user-circle" style="color:#64748b;font-size:14px"></i>
                                    <span>{{ $cat->creator->displayName() }}</span>
                                    @if($cat->created_by == $u->id)
                                        <small style="background:#e0f2fe;color:#0284c7;font-weight:700;padding:1px 6px;border-radius:6px;font-size:10px">You</small>
                                    @endif
                                </div>
                            @else
                                <span class="muted" style="font-size:12px;font-style:italic"><i class="fa-solid fa-shield-halved"></i> System Default</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:center">
                            @if($cat->tasks_count > 0)
                                <a href="{{ route('tasks.index', ['category_id' => $cat->id]) }}" class="chip" style="background:#f1f5f9;color:#0f172a;font-weight:700;font-size:11.5px;padding:2px 8px;border-radius:8px;text-decoration:none" title="View all {{ $cat->tasks_count }} tasks tagged with this category">
                                    <i class="fa-solid fa-list-check" style="color:var(--accent);font-size:10px"></i> {{ $cat->tasks_count }} Tasks
                                </a>
                            @else
                                <span class="muted" style="font-size:11.5px">0 Tasks</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;color:#64748b;font-size:12px">
                            {{ $cat->created_at ? $cat->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'N/A' }}
                        </td>
                        <td style="padding:12px 16px;text-align:right">
                            @if($canManage)
                                <div style="display:inline-flex;align-items:center;gap:6px">
                                    <button type="button" class="btn light" style="font-size:11.5px;padding:4px 10px;border-radius:6px;color:#4f46e5;border:1px solid #c7d2fe" onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}')" title="Edit Category Name">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <button type="button" class="btn light" style="font-size:11.5px;padding:4px 10px;border-radius:6px;color:#dc2626;border:1px solid #fecaca" onclick="openDeleteCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->tasks_count }})" title="Delete Category">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                </div>
                            @else
                                <span class="chip" style="background:#f1f5f9;color:#94a3b8;font-size:10.5px;padding:2px 8px;border-radius:6px" title="Only the employee who created this category or Admin can edit/delete it.">
                                    <i class="fa-solid fa-lock" style="font-size:9px"></i> Read Only
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#64748b">
                            <i class="fa-solid fa-tags" style="font-size:32px;color:#cbd5e1;margin-bottom:8px;display:block"></i>
                            No task categories created yet. Click "+ Add New Category" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: ADD NEW CATEGORY -->
<div id="addCategoryModal" class="modal-bg" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(3px);z-index:99999;align-items:center;justify-content:center" onclick="if(event.target===this) closeAddCategoryModal()">
    <div class="modal" style="background:#fff;max-width:440px;width:92%;padding:24px;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,0.35);margin:auto" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="margin:0;font-size:17px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px">
                <i class="fa-solid fa-tags" style="color:var(--accent)"></i> Add New Task Category
            </h3>
            <button type="button" onclick="closeAddCategoryModal()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <p class="muted" style="font-size:12px;margin-bottom:16px">
            Create a custom task category (e.g. Electricity Bill, Credit Card, Vehicle Repair, Sales Followup, etc.)
        </p>

        <form method="post" action="{{ route('tasks.categories.store') }}" onsubmit="return handleFormSubmitDisable(this, 'Saving Category...')">
            @csrf
            <div style="margin-bottom:16px">
                <label style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:4px">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="addCategoryNameInput" placeholder="e.g. Vehicle Service, Electricity Bill, Client Followup..." required style="width:100%;font-size:13px;padding:9px 12px;border:1.5px solid #cbd5e1;border-radius:8px;outline:none" autocomplete="off">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeAddCategoryModal()" style="font-size:12.5px;padding:8px 14px">Cancel</button>
                <button type="submit" class="btn" style="background:#1e293b;color:#fff;font-size:12.5px;padding:8px 18px">
                    <i class="fa-solid fa-check"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT CATEGORY -->
<div id="editCategoryModal" class="modal-bg" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(3px);z-index:99999;align-items:center;justify-content:center" onclick="if(event.target===this) closeEditCategoryModal()">
    <div class="modal" style="background:#fff;max-width:440px;width:92%;padding:24px;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,0.35);margin:auto" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="margin:0;font-size:17px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px">
                <i class="fa-solid fa-pen-to-square" style="color:var(--accent)"></i> Edit Task Category
            </h3>
            <button type="button" onclick="closeEditCategoryModal()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <p class="muted" style="font-size:12px;margin-bottom:16px">
            Update the name of this category. All associated tasks will immediately reflect the new name.
        </p>

        <form id="editCategoryForm" method="post" action="" onsubmit="return handleFormSubmitDisable(this, 'Updating Category...')">
            @csrf
            @method('PUT')
            <div style="margin-bottom:16px">
                <label style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:4px">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="editCategoryNameInput" required style="width:100%;font-size:13px;padding:9px 12px;border:1.5px solid #cbd5e1;border-radius:8px;outline:none" autocomplete="off">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeEditCategoryModal()" style="font-size:12.5px;padding:8px 14px">Cancel</button>
                <button type="submit" class="btn" style="background:#4f46e5;color:#fff;font-size:12.5px;padding:8px 18px">
                    <i class="fa-solid fa-check"></i> Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DELETE CATEGORY CONFIRMATION -->
<div id="deleteCategoryModal" class="modal-bg" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(3px);z-index:99999;align-items:center;justify-content:center" onclick="if(event.target===this) closeDeleteCategoryModal()">
    <div class="modal" style="background:#fff;max-width:440px;width:92%;padding:24px;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,0.35);margin:auto" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="margin:0;font-size:17px;font-weight:800;color:#dc2626;display:flex;align-items:center;gap:8px">
                <i class="fa-solid fa-triangle-exclamation"></i> Delete Category
            </h3>
            <button type="button" onclick="closeDeleteCategoryModal()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
        </div>

        <p style="font-size:13px;color:#1e293b;line-height:1.5;margin-bottom:10px">
            Are you sure you want to delete category <strong id="deleteCategoryNameDisplay" style="color:#0f172a"></strong>?
        </p>

        <div id="deleteCategoryWarningBox" style="display:none;background:#fef2f2;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:16px;font-size:12px;color:#991b1b">
            <i class="fa-solid fa-circle-info"></i> <span id="deleteCategoryWarningText"></span>
        </div>

        <form id="deleteCategoryForm" method="post" action="" onsubmit="return handleFormSubmitDisable(this, 'Deleting...')">
            @csrf
            @method('DELETE')
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeDeleteCategoryModal()" style="font-size:12.5px;padding:8px 14px">Cancel</button>
                <button type="submit" class="btn" style="background:#dc2626;color:#fff;font-size:12.5px;padding:8px 18px">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCategoryModal() {
    const modal = document.getElementById('addCategoryModal');
    const input = document.getElementById('addCategoryNameInput');
    if (modal) {
        modal.style.display = 'flex';
        if (input) {
            input.value = '';
            setTimeout(() => input.focus(), 50);
        }
    }
}

function closeAddCategoryModal() {
    const modal = document.getElementById('addCategoryModal');
    if (modal) modal.style.display = 'none';
}

function openEditCategoryModal(id, name) {
    const modal = document.getElementById('editCategoryModal');
    const form = document.getElementById('editCategoryForm');
    const input = document.getElementById('editCategoryNameInput');
    if (modal && form && input) {
        form.action = `/tasks/categories/${id}`;
        input.value = name;
        modal.style.display = 'flex';
        setTimeout(() => input.focus(), 50);
    }
}

function closeEditCategoryModal() {
    const modal = document.getElementById('editCategoryModal');
    if (modal) modal.style.display = 'none';
}

function openDeleteCategoryModal(id, name, tasksCount) {
    const modal = document.getElementById('deleteCategoryModal');
    const form = document.getElementById('deleteCategoryForm');
    const nameDisplay = document.getElementById('deleteCategoryNameDisplay');
    const warnBox = document.getElementById('deleteCategoryWarningBox');
    const warnText = document.getElementById('deleteCategoryWarningText');

    if (modal && form) {
        form.action = `/tasks/categories/${id}`;
        if (nameDisplay) nameDisplay.innerText = `'${name}'`;
        if (tasksCount > 0) {
            if (warnBox) warnBox.style.display = 'block';
            if (warnText) warnText.innerText = `There are ${tasksCount} task(s) tagged with this category. If deleted, those tasks will remain intact with category set to General.`;
        } else {
            if (warnBox) warnBox.style.display = 'none';
        }
        modal.style.display = 'flex';
    }
}

function closeDeleteCategoryModal() {
    const modal = document.getElementById('deleteCategoryModal');
    if (modal) modal.style.display = 'none';
}

function filterCategoriesTable() {
    const term = (document.getElementById('catSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.cat-row');
    let count = 0;
    rows.forEach(r => {
        const name = r.getAttribute('data-name') || '';
        const creator = r.getAttribute('data-creator') || '';
        if (!term || name.includes(term) || creator.includes(term)) {
            r.style.display = '';
            count++;
        } else {
            r.style.display = 'none';
        }
    });
    const counter = document.getElementById('visibleCategoryCount');
    if (counter) counter.innerText = count;
}

// Universal submit handler that prevents duplicate form submissions and shows loading spinner
function handleFormSubmitDisable(form, loadingText) {
    if (!form) return true;
    if (form.dataset.submitting === 'true') return false;
    if (form.checkValidity && !form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    form.dataset.submitting = 'true';
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
    }
    return true;
}
</script>
@endsection

