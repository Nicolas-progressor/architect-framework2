<?php

declare(strict_types=1);

/**
 * Debug Panel Styles - CSS для дебаг-панели
 */

?>
<style>
#debug-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: #333;
    border-top: 1px solid #444;
    display: flex;
    align-items: center;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 9pt;
    color: #fff;
    z-index: 999999;
    user-select: none;
}

.debug-col {
    height: 100%;
    display: flex;
    align-items: center;
    padding: 0 12px;
    cursor: pointer;
    border-right: 1px solid #444;
    transition: background 0.2s, transform 0.15s ease-out;
    white-space: nowrap;
}

.debug-col:hover {
    transform: translateY(-2px);
}

.debug-col:hover {
    background: rgba(255,255,255,0.1);
}

.debug-col .icon {
    margin-right: 5px;
    font-size: 10pt;
}

.debug-col .value {
    font-weight: 500;
}

.debug-col[data-color="green"] .value { color: #4caf50; }
.debug-col[data-color="yellow"] .value { color: #ffc107; }
.debug-col[data-color="red"] .value { color: #f44336; }
.debug-col[data-color="orange"] .value { color: #ff9800; }
.debug-col[data-color="gray"] .value { color: #9e9e9e; }
.debug-col[data-color="blue"] .value { color: #2196f3; }

/* Popup Panel */
#debug-popup {
    position: fixed;
    bottom: 30px;
    left: 0;
    right: 0;
    height: 35vh;
    background: #1e1e1e;
    border-top: 1px solid #444;
    display: flex;
    flex-direction: column;
    z-index: 999998;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #fff;
    transform: translateY(100%);
    opacity: 0;
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
    pointer-events: none;
}

#debug-popup.active {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.debug-popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #252525;
    border-bottom: 1px solid #444;
}

.debug-popup-title {
    font-size: 12pt;
    font-weight: 600;
}

.debug-popup-close {
    cursor: pointer;
    font-size: 14pt;
    color: #888;
    padding: 5px 10px;
}

.debug-popup-close:hover {
    color: #fff;
}

.debug-popup-toolbar {
    display: flex;
    gap: 10px;
    padding: 8px 15px;
    background: #2a2a2a;
    border-bottom: 1px solid #333;
}

.debug-popup-btn {
    padding: 4px 12px;
    background: #3a3a3a;
    border: 1px solid #555;
    border-radius: 3px;
    color: #ccc;
    font-size: 9pt;
    cursor: pointer;
    transition: all 0.2s ease-out;
}

.debug-popup-btn:hover {
    background: #4a4a4a;
    transform: translateY(-1px);
}

.debug-popup-btn:active {
    transform: translateY(0);
}

.debug-popup-content {
    flex: 1;
    overflow-y: auto;
    padding: 10px 15px;
}

/* Tables */
.debug-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}

.debug-table th,
.debug-table td {
    padding: 6px 10px;
    text-align: left;
    border-bottom: 1px solid #333;
    color: #e5e7eb;
}

.debug-table th {
    background: #2a2a2a;
    font-weight: 600;
    color: #aaa;
    position: sticky;
    top: 0;
}

.debug-table tr:hover {
    background: rgba(255,255,255,0.03);
}

.debug-table .level-debug { color: #888; }
.debug-table .level-info { color: #64b5f6; }
.debug-table .level-warning { color: #ffc107; }
.debug-table .level-error { color: #f44336; }

.debug-table .slow-query {
    background: rgba(244, 67, 54, 0.1);
}

/* Timeline */
.debug-timeline {
    height: 40px;
    background: #2a2a2a;
    border-radius: 3px;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
}

.debug-timeline-bar {
    position: absolute;
    height: 100%;
    background: linear-gradient(90deg, #2196f3, #64b5f6);
    opacity: 0.7;
}

.debug-timeline-label {
    position: absolute;
    font-size: 8pt;
    color: #888;
    top: 50%;
    transform: translateY(-50%);
}

/* Session tree */
.debug-tree {
    font-size: 9pt;
}

.debug-tree-item {
    padding: 2px 0;
}

.debug-tree-key {
    color: #9e9e9e;
}

.debug-tree-value {
    color: #ce93d8;
}

/* Environment */
.debug-env-list {
    font-size: 9pt;
}

.debug-env-item {
    display: flex;
    padding: 4px 0;
    border-bottom: 1px solid #333;
}

.debug-env-key {
    width: 200px;
    color: #aaa;
}

.debug-popup-content h4 {
    color: #e5e7eb !important;
}

.debug-popup-content p {
    color: #9ca3af;
}

.debug-env-value {
    color: #81c784;
    word-break: break-all;
}

/* Debug Collector Tabs */
.debug-tabs {
    display: flex;
    border-bottom: 1px solid #444;
    margin-bottom: 10px;
}

.debug-tab {
    padding: 8px 16px;
    cursor: pointer;
    color: #888;
    border-bottom: 2px solid transparent;
}

.debug-tab:hover {
    color: #ccc;
}

.debug-tab.active {
    color: #fff;
    border-bottom-color: #2196f3;
}

.debug-tab-content {
    display: none;
    opacity: 0;
    transition: opacity 0.2s ease-out;
}

.debug-tab-content.active {
    display: block;
    opacity: 1;
}

/* Scrollbar */
#debug-popup::-webkit-scrollbar {
    width: 8px;
}

#debug-popup::-webkit-scrollbar-track {
    background: #1e1e1e;
}

#debug-popup::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 4px;
}

#debug-popup::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Time Debug Styles */
.debug-time-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.debug-timeline-visual {
    height: 60px;
    background: #252525;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.timeline-block {
    transition: opacity 0.2s ease-out;
}

.timeline-block:hover {
    opacity: 1 !important;
    z-index: 10;
}
</style>
