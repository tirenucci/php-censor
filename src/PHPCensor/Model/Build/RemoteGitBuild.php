<?php

namespace PHPCensor\Model\Build;

use PHPCensor\Model\Build;
use PHPCensor\Builder;

/**
 * Remote Git Build Model
 * 
 * @author Dan Cryer <dan@block8.co.uk>
 */
class RemoteGitBuild extends Build
{
    /**
    * Get the URL to be used to clone this remote repository.
    */
    protected function getCloneUrl()
    {
        return $this->getProject()->getReference();
    }

    /**
    * Create a working copy by cloning, copying, or similar.
    */
    public function createWorkingCopy(Builder $builder, $buildPath)
    {
        $key = trim($this->getProject()->getSshPrivateKey());

        if (!empty($key)) {
            $success = $this->cloneBySsh($builder, $buildPath);
        } else {
            $success = $this->cloneByHttp($builder, $buildPath);
        }

        if (!$success) {
            $builder->logFailure('Failed to clone remote git repository.');
            return false;
        }

        return $this->handleConfig($builder, $buildPath);
    }

    /**
    * Use an HTTP-based git clone.
    */
    protected function cloneByHttp(Builder $builder, $cloneTo)
    {
        $cmd = 'git clone --recursive ';

        $depth = $builder->getConfig('clone_depth');

        if (!is_null($depth)) {
            $cmd .= ' --depth ' . intval($depth) . ' ';
        }

        $cmd .= ' -b "%s" "%s" "%s"';
        $success = $builder->executeCommand($cmd, $this->getBranch(), $this->getCloneUrl(), $cloneTo);

        if ($success) {
            $success = $this->postCloneSetup($builder, $cloneTo);
        }

        return $success;
    }

    /**
    * Use an SSH-based git clone.
    */
    protected function cloneBySsh(Builder $builder, $cloneTo)
    {
        $keyFile       = $this->writeSshKey($cloneTo);
        $gitSshWrapper = $this->writeSshWrapper($cloneTo, $keyFile);

        // Do the git clone:
        $cmd = 'git clone --recursive ';

        $depth = $builder->getConfig('clone_depth');

        if (!is_null($depth)) {
            $cmd .= ' --depth ' . intval($depth) . ' ';
        }

        $cmd .= ' -b "%s" "%s" "%s"';
        $cmd = 'export GIT_SSH="'.$gitSshWrapper.'" && ' . $cmd;

        $success = $builder->executeCommand($cmd, $this->getBranch(), $this->getCloneUrl(), $cloneTo);

        if ($success) {
            $success = $this->postCloneSetup($builder, $cloneTo);
        }

        // Remove the key file and git wrapper:
        unlink($keyFile);
        unlink($gitSshWrapper);

        return $success;
    }

    /**
     * Handle any post-clone tasks, like switching branches.
     * @param Builder $builder
     * @param $cloneTo
     * @return bool
     */
    protected function postCloneSetup(Builder $builder, $cloneTo)
    {
        $success = true;
        $commit  = $this->getCommitId();
        $chdir   = 'cd "%s"';

        if (!empty($commit) && $commit != 'Manual') {
            $cmd = $chdir . ' && git checkout %s --quiet';
            $success = $builder->executeCommand($cmd, $cloneTo, $commit);
        }

        // Always update the commit hash with the actual HEAD hash
        if ($builder->executeCommand($chdir . ' && git rev-parse HEAD', $cloneTo)) {
            $this->setCommitId(trim($builder->getLastOutput()));
        }

        return $success;
    }
}
