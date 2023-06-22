<style>
    input[type=checkbox], input[type=radio] {padding:0.5em;}
    .form-row .row-col {display:flex; flex-wrap:wrap; flex-direction:row; align-content:start; padding-right:0.75rem;}
    .form-row .row-col > .row:not(.col):not(.col-sm):not(.col-md):not(.col-lg):not(.col-xl) {-ms-flex:0 0 100%;flex:0 0 100%;max-width:100%;}
    .form-row-checkbox {align-items:center;}
    .form-row .col-title-6{width:6rem;}
    .form-row .col-title-7{width:7rem;}
    .form-row .col-title{width:8rem;}
    .form-row .col-title-9{width:9rem;}
    .form-row .col-auto {padding-left:0;}
    .warning + [data-tooltip].fa-question-circle {margin:0.3rem 0.5rem 0;}
    .form-row.form-row-date > div:last-child, .form-row.form-row-image > div:last-child, .form-row.form-row-file > div:last-child {display: flex; flex-wrap: wrap; flex-direction: row; align-items: flex-start;}
    .form-row.form-row-date .DatePicker, .form-row.form-row-image input.form-control[type="text"], .form-row.form-row-file input.form-control[type="text"] {flex-basis: 0; flex-grow: 1; max-width: 100%; width: 100% !important; min-width: auto !important;}
    .image_for_field[data-image] {display:block; content:""; width:240px; height:120px; margin:.1rem .1rem 0 0; border: 1px #ccc solid; background: #fff 50% 50% no-repeat; background-size: contain; cursor: pointer}
    .image_for_field[data-image=""] {display: none}
    input[name^=cover]:not([class*=mtv]) + input[type=button].form-control {margin-top: 0; width: 100px;}
    .form-row.form-row-date input + a, .form-row.form-row-image input + input[type="button"], .form-row.form-row-file input + input[type="button"] {margin-left: -1px;}
    .notifier{position:fixed;display:none;top:0;left:0;width:100%;height:100vh;overflow-y:auto;z-index:9999;background:rgba(255,255,255,0.8);}
    .notifier-txt{position:absolute;width:100%;text-align:center;top:50%;left:50%;background:#fff;padding:30px;font-size:18px;-webkit-transform:translateY(-50%) translateX(-50%);-moz-transform:translateY(-50%) translateX(-50%);-ms-transform:translateY(-50%) translateX(-50%);-o-transform:translateY(-50%) translateX(-50%);transform:translateY(-50%) translateX(-50%);}
    .notifier-error{color:red;}
    .notifier-success{color:green;}
    .is-invalid .select2-selection, .needs-validation ~ span > .select2-dropdown{border-color:red !important;}
    .is-valid .select2-selection, .needs-validation ~ span > .select2-dropdown{border-color:green !important;}
    .post-thumbnail{display:inline-block;max-width:100%;height:auto;padding:4px;line-height:1.42857143;background-color:#fff;border:1px solid #ddd;border-radius:4px;-webkit-transition: all .2s ease-in-out;-o-transition: all .2s ease-in-out;transition: all .2s ease-in-out;}
    #img-preview{border:1px solid #ccc;position:fixed;top:50%;left:50%;width:600px;margin-top:-300px;margin-left:-300px;display:none;}
    .modal{top:50px;font-weight:bold;}
    .fade:not(.show){opacity:initial;}
    .modal-backdrop {background-color:rgba(0, 0, 0, 0.5);}
    .modal-header{margin-top:1rem;}
    table .post-thumbnail{width:70px;height:45px;}
    .badge.bg-seigerit{background-color:#0057b8 !important;color:#ffd700;font-size:85%;}
    .input-group .badge.bg-seigerit{font-size:150%;}
    #copyright{position:fixed;bottom:0;right:0;background-color:#0057b8;padding:3px 7px;border-radius:5px;}
    #copyright img{width:9em;}
    .input-group-text{padding:0.1rem 0.75rem;}
    #preview.form-control{max-width:100px;}
    .dark .sectionBody a small, .darkness .sectionBody a small{color:#b3b3b3;}
    .darkness .select2-selection, .darkness .select2-results__options{border-color:#414449;background-color:#202329;color:#333;}
    .darkness .select2-container--default .select2-selection--multiple .select2-selection__choice{background-color:#eee;color:#495057;}
    #action-btns{text-align:center;width:230px;}
    #actions .btn-group .btn .fas, #_actions .btn-group .btn .fas,
    .sectionTrans .btn-group .btn .fa, .sectionTrans .btn-group .btn .fas{display:none;width:1em;font-size:1em;text-align:center;}
    .dp_container{z-index:1060;}
    .custom-select{font-size:0.8125rem;}
    .tab-row .tab .fas{margin-right:0.5em;font-size:0.875rem;}
    #Button3{color:#fff;}
    .alertify .ajs-footer .ajs-buttons .ajs-button.ajs-ok {color:#fff;background-color:#d9534f;border-color:#d9534f;}
    @media (max-width: 840px) {
        #action-btns{width:80px;}
        #actions .btn-group .btn .fas, #_actions .btn-group .btn .fas,
        .sectionTrans .btn-group .btn .fa, .sectionTrans .btn-group .btn .fas{display:inline-block;}
        .sectionTrans .btn-group .btn span{display:none;}
    }
    .builder .row{display:flex;flex-wrap:wrap;margin-left:-.25rem;margin-right:-.25rem;cursor:default}
    .builder .col-4, .builder .col-8, .builder .col-12, .builder .col, .builder .col-auto{position:relative;width:100%;min-height:0;padding-left:.25rem;padding-right:.25rem}
    .builder .col{flex-basis:0;flex-grow:1;max-width:100%}
    .builder .col-auto{-ms-flex:0 0 auto;flex:0 0 auto;width:auto;max-width:none}
    .builder .col-4{flex:0 0 33.3333%;max-width:33.3333%}
    .builder .col-8{flex:0 0 66.6667%;max-width:66.6667%}
    .builder .col-12{-ms-flex:0 0 100%;flex:0 0 100%;max-width:100%}
    .builder .align-items-center{align-items:center}
    .builder label{margin:0;user-select:none}
    .builder input[type="text"]{cursor:auto}
    .builder .row-col{position:relative;padding:.25rem .25rem 0 .25rem !important;margin-right:-1px !important;min-height:2.4rem;height:100%;border:1px solid}
    .builder .b-resize{position:absolute;top:0;right:-1px;bottom:0;width:.35rem;cursor:col-resize;transition:background-color .25s}
    .builder .fa{font-size:.75rem}
    .builder .b-item{position:relative;padding:.25rem .5rem;margin-bottom:.25rem;border:1px solid}
    .builder .b-btn-del{opacity:.5;transition:.5s opacity}
    .builder .b-btn-del:hover{opacity:1}
    .builder .b-resize{background-color:#0057b8}
    .builder .b-item, .builder .b-tab, .builder .b-item, .builder .row-col, .builder .b-resize, .builder .b-settings .row{border-color:#e0e0e0}
    .builder .row-col-wrap:hover .row-col, .builder .row-col-wrap:hover .b-resize, .builder .b-btn-wrap, .builder .b-resize, .builder .b-settings .col-12:first-child{border-color:#ccc}
    .builder .row-col-wrap:hover .b-resize, .builder .b-settings .b-btn-group label{border-color:#ccc}
    .builder .row-col-wrap:hover .b-resize:hover, .builder .b-resize:hover, .builder .b-resize:active{background-color:#1976d2}
    .darkness .builder .b-resize{background-color:#ffd700}
    .darkness .builder .row-col-wrap:hover .b-resize{background-color:#65686d}
</style>
