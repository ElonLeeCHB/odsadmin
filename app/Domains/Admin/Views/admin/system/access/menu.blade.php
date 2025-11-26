@extends('admin.app')

@section('pageJsCss')
{{-- jstree --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/themes/default/style.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/jstree.min.js"></script>
<style>
.jstree-default .jstree-node {
    margin-left: 24px;
}
.jstree-default .jstree-anchor {
    padding: 4px 8px;
}
.jstree-default .jstree-clicked {
    background: #e7f4ff;
    border-radius: 4px;
}
.menu-form-panel {
    border-left: 1px solid #dee2e6;
    padding-left: 20px;
}
.tree-toolbar {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
#menu-tree {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    background: #fafafa;
}
.form-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}
.form-section:last-child {
    border-bottom: none;
}
.icon-preview {
    font-size: 24px;
    width: 40px;
    text-align: center;
}
</style>
@endsection

@section('columnLeft')
  @include('admin.common.column_left')
@endsection

@section('content')
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="button" id="btn-add-root" class="btn btn-primary" data-bs-toggle="tooltip" title="新增頂層選單">
          <i class="fa-solid fa-plus"></i> 新增頂層
        </button>
        <button type="button" id="btn-expand-all" class="btn btn-light" data-bs-toggle="tooltip" title="展開全部">
          <i class="fa-solid fa-expand"></i>
        </button>
        <button type="button" id="btn-collapse-all" class="btn btn-light" data-bs-toggle="tooltip" title="收合全部">
          <i class="fa-solid fa-compress"></i>
        </button>
      </div>
      <h1>選單管理</h1>
      @include('admin.common.breadcumb')
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      {{-- 左側：樹狀圖 --}}
      <div class="col-lg-5 col-md-12 mb-3">
        <div class="card">
          <div class="card-header">
            <i class="fa-solid fa-sitemap"></i> 選單結構
            <div class="float-end">
              <select id="system-select" class="form-select form-select-sm d-inline-block" style="width: auto;">
                @foreach($systems as $key => $label)
                  <option value="{{ $key }}" @if($current_system == $key) selected @endif>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="card-body">
            <div class="tree-toolbar">
              <div class="input-group input-group-sm">
                <input type="text" id="tree-search" class="form-control" placeholder="搜尋選單...">
                <button class="btn btn-outline-secondary" type="button" id="btn-search-clear">
                  <i class="fa-solid fa-times"></i>
                </button>
              </div>
            </div>
            <div id="menu-tree"></div>
            <div class="mt-3 text-muted small">
              <i class="fa-solid fa-info-circle"></i> 拖放節點可調整層級和順序
            </div>
          </div>
        </div>
      </div>

      {{-- 右側：表單 --}}
      <div class="col-lg-7 col-md-12">
        <div class="card">
          <div class="card-header">
            <i class="fa-solid fa-edit"></i> <span id="form-title">選單詳情</span>
          </div>
          <div class="card-body menu-form-panel">
            <div id="form-placeholder" class="text-center text-muted py-5">
              <i class="fa-solid fa-hand-pointer fa-3x mb-3"></i>
              <p>請從左側選擇一個選單項目，<br>或點擊「新增頂層」按鈕建立新選單</p>
            </div>

            <form id="menu-form" style="display: none;">
              <input type="hidden" id="input-id" name="id" value="">

              <div class="form-section">
                <h6 class="text-muted mb-3">基本資料</h6>

                {{-- 權限代碼 --}}
                <div class="row mb-3">
                  <label for="input-name" class="col-sm-3 col-form-label">權限代碼 <span class="text-danger">*</span></label>
                  <div class="col-sm-9">
                    <input type="text" id="input-name" name="name" class="form-control" placeholder="例如：admin.sales.order">
                    <div class="form-text">格式：系統.模組.作業（例如：admin.sales.order）</div>
                    <div id="error-name" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 顯示名稱 --}}
                <div class="row mb-3">
                  <label for="input-title" class="col-sm-3 col-form-label">顯示名稱 <span class="text-danger">*</span></label>
                  <div class="col-sm-9">
                    <input type="text" id="input-title" name="title" class="form-control" placeholder="例如：訂單管理">
                    <div id="error-title" class="invalid-feedback"></div>
                  </div>
                </div>

                {{-- 父層選單 --}}
                <div class="row mb-3">
                  <label for="input-parent_id" class="col-sm-3 col-form-label">父層選單</label>
                  <div class="col-sm-9">
                    <select id="input-parent_id" name="parent_id" class="form-select">
                      <option value="">-- 無（頂層） --</option>
                    </select>
                    <div id="error-parent_id" class="invalid-feedback"></div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h6 class="text-muted mb-3">顯示設定</h6>

                {{-- 圖示 --}}
                <div class="row mb-3">
                  <label for="input-icon" class="col-sm-3 col-form-label">圖示</label>
                  <div class="col-sm-9">
                    <div class="input-group">
                      <span class="input-group-text icon-preview"><i id="icon-preview" class="fas fa-file"></i></span>
                      <input type="text" id="input-icon" name="icon" class="form-control" placeholder="例如：fas fa-shopping-cart">
                    </div>
                    <div class="form-text">FontAwesome 圖示 class（<a href="https://fontawesome.com/icons" target="_blank">查看圖示</a>）</div>
                  </div>
                </div>

                {{-- 排序 --}}
                <div class="row mb-3">
                  <label for="input-sort_order" class="col-sm-3 col-form-label">排序</label>
                  <div class="col-sm-9">
                    <input type="number" id="input-sort_order" name="sort_order" class="form-control" value="0" min="0">
                    <div class="form-text">數字越小越前面（拖放會自動調整）</div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <h6 class="text-muted mb-3">其他</h6>

                {{-- 說明 --}}
                <div class="row mb-3">
                  <label for="input-description" class="col-sm-3 col-form-label">說明</label>
                  <div class="col-sm-9">
                    <textarea id="input-description" name="description" class="form-control" rows="2" placeholder="選單說明（選填）"></textarea>
                  </div>
                </div>
              </div>

              {{-- 按鈕 --}}
              <div class="d-flex justify-content-between">
                <div>
                  <button type="button" id="btn-delete" class="btn btn-danger" style="display: none;">
                    <i class="fa-solid fa-trash"></i> 刪除
                  </button>
                </div>
                <div>
                  <button type="button" id="btn-cancel" class="btn btn-light me-2">
                    <i class="fa-solid fa-times"></i> 取消
                  </button>
                  <button type="button" id="btn-add-child" class="btn btn-success me-2" style="display: none;">
                    <i class="fa-solid fa-plus"></i> 新增子選單
                  </button>
                  <button type="submit" id="btn-save" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> 儲存
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('buttom')
<script type="text/javascript">
(function() {
    // URLs
    const urls = {
        tree: '{{ $tree_url }}',
        store: '{{ $store_url }}',
        show: '{{ $show_url }}',
        update: '{{ $update_url }}',
        destroy: '{{ $destroy_url }}',
        move: '{{ $move_url }}',
        parents: '{{ $parents_url }}'
    };

    let currentSystem = '{{ $current_system }}';
    let currentMenuId = null;
    let isNewMode = false;

    // 初始化 jstree
    function initTree() {
        $('#menu-tree').jstree('destroy');

        $('#menu-tree').jstree({
            'core': {
                'data': {
                    'url': function() {
                        return urls.tree + '?system=' + currentSystem;
                    },
                    'dataType': 'json'
                },
                'check_callback': true,
                'themes': {
                    'responsive': false,
                    'variant': 'large'
                }
            },
            'plugins': ['dnd', 'search', 'wholerow'],
            'dnd': {
                'is_draggable': function(nodes) {
                    return true;
                }
            },
            'search': {
                'show_only_matches': true,
                'show_only_matches_children': true
            }
        });

        // 選擇節點
        $('#menu-tree').on('select_node.jstree', function(e, data) {
            loadMenuDetail(data.node.id);
        });

        // 拖放完成
        $('#menu-tree').on('move_node.jstree', function(e, data) {
            const nodeId = data.node.id;
            const newParentId = data.parent;
            const position = data.position;

            $.ajax({
                url: urls.move,
                method: 'POST',
                data: {
                    id: nodeId,
                    parent_id: newParentId,
                    position: position
                },
                success: function(response) {
                    showAlert('success', response.success || '移動成功');
                },
                error: function(xhr) {
                    showAlert('danger', xhr.responseJSON?.error || '移動失敗');
                    refreshTree();
                }
            });
        });
    }

    // 載入選單詳情
    function loadMenuDetail(id) {
        currentMenuId = id;
        isNewMode = false;

        $.ajax({
            url: urls.show.replace('__ID__', id),
            method: 'GET',
            success: function(data) {
                showForm();
                $('#form-title').text('編輯選單');
                $('#input-id').val(data.id);
                $('#input-name').val(data.name);
                $('#input-title').val(data.title);
                $('#input-icon').val(data.icon || '');
                $('#input-sort_order').val(data.sort_order || 0);
                $('#input-description').val(data.description || '');

                updateIconPreview();
                loadParentOptions(id, data.parent_id);

                $('#btn-delete').show();
                $('#btn-add-child').show();
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.error || '載入失敗');
            }
        });
    }

    // 載入父層選項
    function loadParentOptions(excludeId, selectedId) {
        $.ajax({
            url: urls.parents,
            method: 'GET',
            data: {
                system: currentSystem,
                exclude_id: excludeId || ''
            },
            success: function(data) {
                const $select = $('#input-parent_id');
                $select.find('option:not(:first)').remove();

                data.forEach(function(item) {
                    const indent = '\u3000'.repeat(item.level);
                    const selected = (selectedId && item.id == selectedId) ? 'selected' : '';
                    $select.append(`<option value="${item.id}" ${selected}>${indent}${item.title} (${item.name})</option>`);
                });
            }
        });
    }

    // 顯示表單
    function showForm() {
        $('#form-placeholder').hide();
        $('#menu-form').show();
        clearErrors();
    }

    // 隱藏表單
    function hideForm() {
        $('#form-placeholder').show();
        $('#menu-form').hide();
        currentMenuId = null;
        isNewMode = false;
    }

    // 重設表單
    function resetForm() {
        $('#menu-form')[0].reset();
        $('#input-id').val('');
        $('#input-name').val(currentSystem + '.');
        $('#btn-delete').hide();
        $('#btn-add-child').hide();
        clearErrors();
        updateIconPreview();
    }

    // 清除錯誤
    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    // 顯示錯誤
    function showErrors(errors) {
        clearErrors();
        for (const field in errors) {
            const $input = $(`#input-${field}`);
            $input.addClass('is-invalid');
            $(`#error-${field}`).text(errors[field][0]);
        }
    }

    // 更新圖示預覽
    function updateIconPreview() {
        const icon = $('#input-icon').val() || 'fas fa-file';
        $('#icon-preview').attr('class', icon);
    }

    // 重新載入樹
    function refreshTree() {
        $('#menu-tree').jstree('refresh');
    }

    // 顯示提示
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#alert').html(alertHtml);
        setTimeout(function() {
            $('#alert .alert').alert('close');
        }, 3000);
    }

    // 事件綁定
    $(document).ready(function() {
        initTree();

        // 切換系統
        $('#system-select').on('change', function() {
            currentSystem = $(this).val();
            hideForm();
            refreshTree();
        });

        // 搜尋
        let searchTimeout;
        $('#tree-search').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const query = $('#tree-search').val();
                $('#menu-tree').jstree('search', query);
            }, 300);
        });

        $('#btn-search-clear').on('click', function() {
            $('#tree-search').val('');
            $('#menu-tree').jstree('clear_search');
        });

        // 展開/收合
        $('#btn-expand-all').on('click', function() {
            $('#menu-tree').jstree('open_all');
        });

        $('#btn-collapse-all').on('click', function() {
            $('#menu-tree').jstree('close_all');
        });

        // 新增頂層
        $('#btn-add-root').on('click', function() {
            isNewMode = true;
            currentMenuId = null;
            showForm();
            resetForm();
            $('#form-title').text('新增頂層選單');
            $('#input-parent_id').val('');
            loadParentOptions(null, null);
        });

        // 新增子選單
        $('#btn-add-child').on('click', function() {
            const parentId = currentMenuId;
            const parentName = $('#input-name').val();

            isNewMode = true;
            currentMenuId = null;
            showForm();
            resetForm();
            $('#form-title').text('新增子選單');
            $('#input-name').val(parentName + '.');

            loadParentOptions(null, parentId);
        });

        // 取消
        $('#btn-cancel').on('click', function() {
            hideForm();
            $('#menu-tree').jstree('deselect_all');
        });

        // 圖示預覽
        $('#input-icon').on('input', updateIconPreview);

        // 儲存
        $('#menu-form').on('submit', function(e) {
            e.preventDefault();

            const data = {
                name: $('#input-name').val(),
                title: $('#input-title').val(),
                parent_id: $('#input-parent_id').val() || null,
                icon: $('#input-icon').val(),
                sort_order: $('#input-sort_order').val(),
                description: $('#input-description').val()
            };

            let url, method;
            if (isNewMode) {
                url = urls.store;
                method = 'POST';
            } else {
                url = urls.update.replace('__ID__', currentMenuId);
                method = 'PUT';
            }

            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    showAlert('success', response.success || '儲存成功');
                    refreshTree();

                    if (isNewMode && response.data?.id) {
                        // 新增後載入該項目
                        setTimeout(function() {
                            loadMenuDetail(response.data.id);
                        }, 500);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    }
                    showAlert('danger', xhr.responseJSON?.error || '儲存失敗');
                }
            });
        });

        // 刪除
        $('#btn-delete').on('click', function() {
            if (!currentMenuId) return;

            if (!confirm('確定要刪除此選單嗎？')) return;

            $.ajax({
                url: urls.destroy.replace('__ID__', currentMenuId),
                method: 'DELETE',
                success: function(response) {
                    showAlert('success', response.success || '刪除成功');
                    hideForm();
                    refreshTree();
                },
                error: function(xhr) {
                    showAlert('danger', xhr.responseJSON?.error || '刪除失敗');
                }
            });
        });
    });
})();
</script>
@endsection
