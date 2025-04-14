<?php
// File: views/chat/index.php
?>
<div class="chat-container">
    <div class="chat-sidebar">
        <div class="user-profile">
            <div class="user-avatar">
                <img src="<?= $user->avatar ?? '/public/assets/images/default-avatar.png' ?>" alt="<?= $user->username ?>">
                <span class="status-indicator <?= $user->status ?? 'offline' ?>"></span>
            </div>
            <div class="user-info">
                <h3><?= $user->username ?></h3>
                <p class="status-message"><?= $user->statusMessage ?? 'Available' ?></p>
            </div>
        </div>
        
        <div class="chat-tabs">
            <button class="tab-button active" data-tab="chats">Chats</button>
            <button class="tab-button" data-tab="groups">Groups</button>
            <button class="tab-button" data-tab="friends">Friends</button>
        </div>
        
        <div class="tab-content active" id="chats-tab">
            <div class="search-container">
                <input type="text" placeholder="Search chats..." id="chat-search">
            </div>
            <ul class="chat-list" id="private-chats">
                <!-- Chat list will be populated via JavaScript -->
                <li class="chat-loading">Loading chats...</li>
            </ul>
        </div>
        
        <div class="tab-content" id="groups-tab">
            <div class="search-container">
                <input type="text" placeholder="Search groups..." id="group-search">
            </div>
            <ul class="group-list" id="group-chats">
                <!-- Group list will be populated via JavaScript -->
                <li class="group-loading">Loading groups...</li>
            </ul>
            <button class="create-group-btn">Create New Group</button>
        </div>
        
        <div class="tab-content" id="friends-tab">
            <div class="search-container">
                <input type="text" placeholder="Search friends..." id="friend-search">
            </div>
            <ul class="friend-list" id="friends-list">
                <!-- Friend list will be populated via JavaScript -->
                <li class="friend-loading">Loading friends...</li>
            </ul>
            <button class="add-friend-btn">Add Friend</button>
        </div>
    </div>
    
    <div class="chat-main">
        <div class="chat-header">
            <div class="chat-info">
                <h2 id="current-chat-name">Select a chat</h2>
                <p id="current-chat-status"></p>
            </div>
            <div class="chat-actions">
                <button id="voice-call-btn" title="Voice Call" disabled><i class="icon-phone"></i></button>
                <button id="video-call-btn" title="Video Call" disabled><i class="icon-video"></i></button>
                <button id="screen-share-btn" title="Share Screen" disabled><i class="icon-screen"></i></button>
                <button id="chat-menu-btn" title="More Options" disabled><i class="icon-menu"></i></button>
            </div>
        </div>
        
        <div class="chat-messages" id="message-container">
            <div class="no-chat-selected">
                <div class="no-chat-icon">
                    <i class="icon-chat"></i>
                </div>
                <h3>Select a chat to start messaging</h3>
                <p>You can select a private chat, group, or start a new conversation.</p>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="attachment-options">
                <button id="emoji-btn" title="Emoji"><i class="icon-emoji"></i></button>
                <button id="file-btn" title="Send File"><i class="icon-attachment"></i></button>
                <button id="image-btn" title="Send Image"><i class="icon-image"></i></button>
            </div>
            <div class="message-input">
                <textarea id="message-input" placeholder="Type a message..." disabled></textarea>
            </div>
            <button id="send-message-btn" disabled><i class="icon-send"></i></button>
        </div>
    </div>
    
    <div class="chat-info-panel" id="info-panel">
        <!-- Will display user/group info based on selected chat -->
    </div>
</div>

<!-- Templates for dynamic content -->
<template id="message-template">
    <div class="message">
        <div class="message-avatar">
            <img src="" alt="">
        </div>
        <div class="message-content">
            <div class="message-header">
                <span class="message-sender"></span>
                <span class="message-time"></span>
            </div>
            <div class="message-body"></div>
        </div>
    </div>
</template>

<template id="chat-item-template">
    <li class="chat-item" data-id="">
        <div class="chat-avatar">
            <img src="" alt="">
            <span class="status-indicator"></span>
        </div>
        <div class="chat-info">
            <h4 class="chat-name"></h4>
            <p class="chat-last-message"></p>
        </div>
        <div class="chat-meta">
            <span class="chat-time"></span>
            <span class="unread-count"></span>
        </div>
    </li>
</template>

<!-- Modals -->
<div class="modal" id="create-group-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Create New Group</h2>
        <form id="create-group-form">
            <div class="form-group">
                <label for="group-name">Group Name</label>
                <input type="text" id="group-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="group-description">Description</label>
                <textarea id="group-description" name="description"></textarea>
            </div>
            <div class="form-group">
                <label>Privacy</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="privacy" value="public" checked> Public
                    </label>
                    <label>
                        <input type="radio" name="privacy" value="private"> Private
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Add Members</label>
                <div class="friend-selector" id="group-member-selector"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Create Group</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="add-friend-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Add Friend</h2>
        <form id="add-friend-form">
            <div class="form-group">
                <label for="friend-username">Username</label>
                <input type="text" id="friend-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="friend-message">Message (Optional)</label>
                <textarea id="friend-message" name="message"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Send Request</button>
            </div>
        </form>
    </div>
</div>