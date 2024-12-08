<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Interfaces;

interface ConverterInterface
{
    /**
     * Video işleme
     * 
     * @param string $url Video URL'i
     * @return array İşlem sonucu
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function processVideo(string $url): array;

    /**
     * Video bilgilerini getir
     * 
     * @param string $url Video URL'i
     * @return array Video bilgileri
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function getVideoInfo(string $url): array;

    /**
     * Video indirme
     * 
     * @param string $url Video URL'i
     * @param string $id Video ID
     * @return string İndirilen dosya yolu
     * @throws \Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException
     */
    public function downloadVideo(string $url, string $id): string;
} 