<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppMemberChapterProgress extends Model
{
    use HasFactory;

    protected $table = 'app_member_chapter_progress';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'course_id',
        'chapter_id',
        'learned_duration',
        'total_duration',
        'progress',
        'last_position',
        'is_completed',
        'complete_time',
        'view_count',
        'first_view_time',
        'last_view_time',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'id' => 'integer',
        'member_id' => 'integer',
        'course_id' => 'integer',
        'chapter_id' => 'integer',
        'learned_duration' => 'integer',
        'total_duration' => 'integer',
        'progress' => 'decimal:2',
        'last_position' => 'integer',
        'is_completed' => 'integer',
        'complete_time' => 'datetime',
        'view_count' => 'integer',
        'first_view_time' => 'datetime',
        'last_view_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(AppMemberBase::class, 'member_id', 'member_id');
    }

    /**
     * 关联课程
     */
    public function course()
    {
        return $this->belongsTo(AppCourseBase::class, 'course_id', 'course_id');
    }

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo(AppCourseChapter::class, 'chapter_id', 'chapter_id');
    }

    /**
     * 查询作用域：按用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域：按课程
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * 查询作用域：已完成
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', 1);
    }

    /**
     * 获取或创建进度记录
     */
    public static function getOrCreate(int $memberId, int $courseId, int $chapterId): self
    {
        $progress = self::byMember($memberId)
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$progress) {
            $chapter = AppCourseChapter::find($chapterId);
            $progress = self::create([
                'member_id' => $memberId,
                'course_id' => $courseId,
                'chapter_id' => $chapterId,
                'total_duration' => $chapter ? $chapter->duration : 0,
                'first_view_time' => now(),
                'create_time' => now(),
                'update_time' => now(),
            ]);
        }

        return $progress;
    }

    /**
     * 更新学习进度
     */
    public function updateLearnProgress(int $position, int $duration = 0): void
    {
        $this->last_position = $position;
        $this->learned_duration += $duration;
        $this->view_count++;
        $this->last_view_time = now();
        $this->update_time = now();

        // 计算进度百分比
        if ($this->total_duration > 0) {
            $this->progress = min(100, round(($this->learned_duration / $this->total_duration) * 100, 2));
        }

        // 检查是否完成（进度>=90%视为完成）
        if ($this->progress >= 90 && !$this->is_completed) {
            $this->is_completed = 1;
            $this->complete_time = now();
        }

        $this->save();
    }

    /**
     * 标记完成
     */
    public function markCompleted(): bool
    {
        $this->is_completed = 1;
        $this->complete_time = now();
        $this->progress = 100;
        $this->update_time = now();
        return $this->save();
    }
}
