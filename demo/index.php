<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube MP3 Dönüştürücü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff0000;
            --secondary-color: #1a1a1a;
            --accent-color: #2ecc71;
            --bg-gradient: linear-gradient(135deg, #f6f8fd 0%, #f1f4f9 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-transform: translateY(-5px);
        }
        
        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .main-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .hero-section {
            text-align: center;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .hero-title i {
            color: var(--primary-color);
            transform: scale(1);
            transition: transform 0.3s ease;
            display: inline-block;
        }
        
        .hero-title i:hover {
            transform: scale(1.1);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 3rem;
        }
        
        .search-box {
            background: white;
            border-radius: 30px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .search-box:focus-within {
            transform: var(--hover-transform);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .url-input {
            border: 2px solid #e9ecef;
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .url-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,0,0,0.1);
            background: white;
        }
        
        .convert-btn {
            border-radius: 20px;
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(45deg, var(--primary-color), #ff4b4b);
            border: none;
            transition: all 0.3s ease;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .convert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,0,0,0.3);
            background: linear-gradient(45deg, #ff4b4b, var(--primary-color));
        }
        
        .convert-btn:active {
            transform: translateY(0);
        }
        
        .convert-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .video-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(20px);
        }
        
        .video-card.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .video-thumbnail {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .video-info {
            padding: 1.5rem;
        }
        
        .video-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .video-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .video-meta i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .video-meta span {
            margin-right: 1rem;
        }
        
        .progress-wrapper {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transition: width 0.3s ease;
        }
        
        .status-text {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .status-badge.converting {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .status-badge.completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-badge.error {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .features-section {
            padding: 4rem 0;
            background: white;
            border-radius: 30px;
            margin-top: 4rem;
            box-shadow: var(--card-shadow);
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: var(--hover-transform);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--bg-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .feature-text {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .converting-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="hero-section">
            <h1 class="hero-title">
                <i class="fab fa-youtube"></i>
                YouTube MP3 Dönüştürücü
            </h1>
            <p class="hero-subtitle">
                YouTube videolarını yüksek kaliteli MP3 formatına dönüştürün
            </p>
            <div class="search-box">
                <form id="convertForm" class="mb-0">
                    <div class="input-group">
                        <input type="text" class="form-control url-input" id="url" name="url" 
                               placeholder="YouTube video URL'sini yapıştırın" required>
                        <button class="btn convert-btn" type="submit">
                            <i class="fas fa-sync-alt"></i>Dönüştür
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="videoList">
            <!-- Video kartları buraya eklenecek -->
        </div>
        
        <div class="features-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h3 class="feature-title">Hızlı Dönüşüm</h3>
                            <p class="feature-text">
                                Saniyeler içinde yüksek kaliteli MP3 formatına dönüştürün
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-music"></i>
                            </div>
                            <h3 class="feature-title">Yüksek Kalite</h3>
                            <p class="feature-text">
                                320kbps yüksek kaliteli ses formatında indirin
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <h3 class="feature-title">Playlist Desteği</h3>
                            <p class="feature-text">
                                Tüm çalma listesini tek tıkla dönüştürün
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('jQuery sürümü:', $.fn.jquery);
            
            const videoList = $('#videoList');
            const convertForm = $('#convertForm');
            const urlInput = $('#url');
            const convertBtn = $('.convert-btn');
            const progressIntervals = {};
            let isProcessing = false;
            
            function showLoading() {
                isProcessing = true;
                convertBtn.prop('disabled', true);
                convertBtn.html('<i class="fas fa-spinner fa-spin"></i> İşleniyor...');
                
                // Loading card ekle
                const loadingCard = $(`
                    <div class="video-card loading-card show">
                        <div class="video-info text-center p-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                            <h3 class="video-title">Video Bilgileri Alınıyor</h3>
                            <p class="text-muted">Bu işlem biraz zaman alabilir, lütfen bekleyin...</p>
                        </div>
                    </div>
                `);
                videoList.prepend(loadingCard);
            }
            
            function hideLoading() {
                isProcessing = false;
                convertBtn.prop('disabled', false);
                convertBtn.html('<i class="fas fa-sync-alt"></i> Dönüştür');
                $('.loading-card').remove();
            }
            
            function showError(message, isTimeout = false) {
                const errorHtml = `
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>${message}
                        ${isTimeout ? '<br><small class="mt-2 d-block">İşlem arka planda devam ediyor olabilir. Birkaç dakika sonra tekrar kontrol edin.</small>' : ''}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                videoList.prepend(errorHtml);
            }
            
            function createVideoCard(video) {
                const card = $(`
                    <div class="video-card" id="video-${video.id}">
                        <img src="https://i.ytimg.com/vi/${video.id}/maxresdefault.jpg" class="video-thumbnail" alt="${video.title}">
                        <div class="video-info">
                            <h3 class="video-title">${video.title}</h3>
                            <div class="video-meta">
                                <span><i class="fas fa-clock"></i> Hazırlanıyor...</span>
                                <span><i class="fas fa-music"></i> MP3</span>
                            </div>
                            <span class="status-badge converting">Dönüştürülüyor</span>
                            <div class="progress-wrapper">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <p class="status-text">Dönüştürme başlatılıyor...</p>
                            </div>
                        </div>
                    </div>
                `);
                
                setTimeout(() => card.addClass('show'), 100);
                return card;
            }
            
            function updateVideoStatus(videoId, status) {
                const card = $(`#video-${videoId}`);
                if (!card.length) return;
                
                const badge = card.find('.status-badge');
                const progressBar = card.find('.progress-bar');
                const statusText = card.find('.status-text');
                const thumbnail = card.find('.video-thumbnail');
                
                badge.removeClass('converting completed error');
                thumbnail.removeClass('converting-animation');
                
                if (status.stage === 'downloading') {
                    badge.addClass('converting').text('İndiriliyor');
                    thumbnail.addClass('converting-animation');
                    progressBar.css('width', `${status.progress}%`);
                    statusText.text(status.message);
                } else if (status.stage === 'converting') {
                    badge.addClass('converting').text('Dönüştürülüyor');
                    thumbnail.addClass('converting-animation');
                    progressBar.css('width', `${status.progress}%`);
                    statusText.text(status.message);
                } else if (status.stage === 'completed') {
                    badge.addClass('completed').text('Tamamlandı');
                    progressBar.css('width', '100%');
                    statusText.text('Dönüştürme tamamlandı!');
                    thumbnail.removeClass('converting-animation');
                    clearInterval(progressIntervals[videoId]);
                } else if (status.stage === 'error') {
                    badge.addClass('error').text('Hata');
                    progressBar.css('width', '0%');
                    statusText.text(status.message);
                    thumbnail.removeClass('converting-animation');
                    clearInterval(progressIntervals[videoId]);
                }
            }
            
            function startProgressCheck(videoId) {
                if (progressIntervals[videoId]) {
                    clearInterval(progressIntervals[videoId]);
                }
                
                let retryCount = 0;
                const maxRetries = 3;
                
                progressIntervals[videoId] = setInterval(() => {
                    $.ajax({
                        url: 'process.php',
                        method: 'GET',
                        data: {
                            action: 'progress',
                            id: videoId
                        },
                        timeout: 10000,
                        success: function(response) {
                            console.log('İlerleme yanıtı:', response);
                            retryCount = 0; // Başarılı yanıtta retry sayacını sıfırla
                            
                            if (response.success && response.data) {
                                updateVideoStatus(videoId, response.data);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('İlerleme kontrolü hatası:', error);
                            retryCount++;
                            
                            if (retryCount >= maxRetries) {
                                clearInterval(progressIntervals[videoId]);
                                updateVideoStatus(videoId, {
                                    stage: 'error',
                                    message: 'İlerleme bilgisi alınamadı'
                                });
                            }
                        }
                    });
                }, 2000); // 2 saniyede bir kontrol et
            }
            
            convertForm.on('submit', function(e) {
                e.preventDefault();
                console.log('Form submit edildi');
                
                if (isProcessing) {
                    showError('Şu anda başka bir işlem devam ediyor, lütfen bekleyin.');
                    return;
                }
                
                const url = urlInput.val().trim();
                if (!url) {
                    showError('Lütfen bir YouTube URL\'si girin');
                    return;
                }
                
                // YouTube URL kontrolü
                if (!url.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|music\.youtube\.com)\/.+$/)) {
                    showError('Lütfen geçerli bir YouTube URL\'si girin');
                    return;
                }
                
                showLoading();
                
                $.ajax({
                    url: 'process.php',
                    method: 'POST',
                    data: {
                        action: 'process',
                        url: url
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('İstek gönderiliyor...');
                        console.log('URL:', url);
                    },
                    success: function(response) {
                        console.log('Sunucu yanıtı:', response);
                        urlInput.val('');
                        
                        if (response.success && response.results && response.results.length) {
                            $('.loading-card').remove(); // Loading card'ı kaldır
                            response.results.forEach(video => {
                                const card = createVideoCard(video);
                                videoList.prepend(card);
                                startProgressCheck(video.id);
                            });
                        } else {
                            showError(response.error || 'Video bilgileri alınamadı');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax hatası:', error);
                        console.error('Status:', status);
                        console.error('XHR:', xhr.responseText);
                        
                        let errorMessage = 'Dönüştürme işlemi başlatılamadı';
                        let isTimeout = false;
                        
                        if (status === 'timeout') {
                            errorMessage = 'İstek zaman aşımına uğradı. İşlem arka planda devam ediyor olabilir.';
                            isTimeout = true;
                            
                            // Timeout durumunda 30 saniye sonra otomatik kontrol
                            setTimeout(() => {
                                location.reload();
                            }, 30000);
                        } else if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.error || errorMessage;
                            } catch(e) {
                                console.error('JSON parse hatası:', e);
                            }
                        }
                        
                        showError(errorMessage, isTimeout);
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            });
            
            // Sayfa yüklendiğinde URL input'una fokuslan
            urlInput.focus();
        });
    </script>
</body>
</html> 